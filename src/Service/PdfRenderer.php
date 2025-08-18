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

    public function __construct(
        private readonly TwigEnvironment $twig,
        ParameterBagInterface $params
    ) {
        $this->projectDir  = (string) $params->get('kernel.project_dir');
        $this->wkhtmltopdf = $_ENV['WKHTMLTOPDF_PATH'] ?? 'wkhtmltopdf';
    }

    public function renderHtml(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }

    public function renderToPdf(string $html, ?string $outputAbsolutePath = null): string
    {
        $fs = new Filesystem();
        $out = $outputAbsolutePath ?? ($this->projectDir . '/public/invoices/' . uniqid('invoice_', true) . '.pdf');
        $dir = \dirname($out);
        if (!$fs->exists($dir)) {
            $fs->mkdir($dir, 0775);
        }

        $tmpHtml = $this->projectDir . '/public/invoices/' . uniqid('invoice_', true) . '.html';
        file_put_contents($tmpHtml, $html);

        $process = new Process([$this->wkhtmltopdf, '-q', '--encoding', 'utf-8', '--page-size', 'A4', $tmpHtml, $out]);
        $process->setTimeout(60)->run();
        @unlink($tmpHtml);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $out;
    }

    public function renderTemplateToPdf(string $template, array $context = [], ?string $outputAbsolutePath = null): string
    {
        return $this->renderToPdf($this->renderHtml($template, $context), $outputAbsolutePath);
    }
}
