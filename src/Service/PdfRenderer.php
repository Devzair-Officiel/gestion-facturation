<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Twig\Environment as TwigEnvironment;

final class PdfRenderer
{
    private string $projectDir;
    private string $wkhtmltopdf;
    private string $publicPath;

    public function __construct(
        private readonly TwigEnvironment $twig,
        ParameterBagInterface $params
    ) {
        $this->projectDir  = (string) $params->get('kernel.project_dir');
        $this->publicPath  = $this->projectDir . '/public';
        // Lis l'un ou l'autre (selon ce que tu as mis dans .env)
        $this->wkhtmltopdf = $_ENV['WKHTMLTOPDF_BINARY']
            ?? $_ENV['WKHTMLTOPDF_PATH']
            ?? '/usr/bin/wkhtmltopdf';
    }

    public function renderHtml(string $template, array $context = []): string
    {
        // Injecte public_path pour permettre des liens file:// depuis Twig si besoin
        $context['public_path'] = $this->publicPath;
        return $this->twig->render($template, $context);
    }

    public function renderToPdf(string $html, ?string $outputAbsolutePath = null): string
    {
        $fs  = new Filesystem();
        $out = $outputAbsolutePath ?? ($this->projectDir . '/public/invoices/' . uniqid('invoice_', true) . '.pdf');
        $dir = \dirname($out);
        if (!$fs->exists($dir)) {
            $fs->mkdir($dir, 0775);
        }

        // Écrit le HTML dans public/invoices puis l’ouvre en file://
        $tmpHtml = $this->projectDir . '/public/invoices/' . uniqid('invoice_', true) . '.html';
        file_put_contents($tmpHtml, $html);
        $input = 'file://' . $tmpHtml; // IMPORTANT : schéma file://

        // Options clés : accès fichiers locaux + autorisation du dossier public
        $cmd = [
            $this->wkhtmltopdf,
            '--enable-local-file-access',                  // wkhtmltopdf >= 0.12.6
            '--allow',
            $this->publicPath,                 // utile toutes versions
            '--encoding',
            'utf-8',
            '--page-size',
            'A4',
            $input,
            $out,
        ];

        // Variables d’environnement pour éviter les warnings/runtime fontconfig
        $env = [
            'XDG_RUNTIME_DIR' => '/tmp/runtime-www-data',
            // Optionnel : si tu as mis un cache fontconfig custom
            // 'XDG_CACHE_HOME'  => $this->projectDir . '/.cache',
        ];

        $process = new Process($cmd, null, $env, null, 60);
        $process->run();

        @unlink($tmpHtml);

        if (!$process->isSuccessful()) {
            // remonte l'erreur wkhtmltopdf telle quelle
            throw new ProcessFailedException($process);
        }

        return $out;
    }

    public function renderTemplateToPdf(string $template, array $context = [], ?string $outputAbsolutePath = null): string
    {
        // public_path est injecté dans renderHtml()
        return $this->renderToPdf($this->renderHtml($template, $context), $outputAbsolutePath);
    }
}
