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
    private string $publicPath;
    private string $wkhtmltopdf;

    public function __construct(
        private readonly TwigEnvironment $twig,
        ParameterBagInterface $params
    ) {
        $this->projectDir  = (string) $params->get('kernel.project_dir');
        $this->publicPath  = $this->projectDir . '/public';
        $this->wkhtmltopdf = $_ENV['WKHTMLTOPDF_BINARY']
            ?? $_ENV['WKHTMLTOPDF_PATH']
            ?? '/usr/bin/wkhtmltopdf';
    }

    public function renderHtml(string $template, array $context = []): string
    {
        // pour pouvoir référencer des assets locaux dans le template via file://
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

        // Commande wkhtmltopdf : on lit le HTML sur STDIN ("-")
        $cmd = [
            $this->wkhtmltopdf,
            '--enable-local-file-access',             // >= 0.12.6
            '--allow',
            $this->publicPath,            // autorise l’accès au /public
            '--encoding',
            'utf-8',
            '--page-size',
            'A4',
            '-',                                      // <<< lire depuis stdin
            $out,
        ];

        // Éviter les warnings/erreurs runtime Qt/fontconfig
        $env = [
            'XDG_RUNTIME_DIR' => '/tmp/runtime-www-data',
            // 'XDG_CACHE_HOME'  => $this->projectDir . '/.cache', // si tu l’utilises
        ];

        // On donne le HTML directement en entrée du process
        $process = new Process($cmd, null, $env, $html, 60);
        $process->run();

        if (!$process->isSuccessful()) {
            // remonte l'info d'erreur réelle
            throw new ProcessFailedException($process);
        }

        return $out;
    }

    public function renderTemplateToPdf(string $template, array $context = [], ?string $outputAbsolutePath = null): string
    {
        return $this->renderToPdf($this->renderHtml($template, $context), $outputAbsolutePath);
    }
}
