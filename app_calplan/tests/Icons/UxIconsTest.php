<?php

namespace App\Tests\Icons;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRendererInterface;

final class UxIconsTest extends WebTestCase
{
    public function testInicioRenderizaIconoBootstrapInlineSinCdn(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();

        $svg = $crawler->filter('.navbar-brand svg');
        self::assertCount(1, $svg, 'La barra de navegación debe renderizar el ícono bi:calendar-week como SVG inline.');
        self::assertSame('true', $svg->attr('aria-hidden'));
        self::assertSame('currentColor', $svg->attr('fill'));

        // Comprueba que no existan enlaces a recursos externos (CDN o APIs de íconos) en el HTML servido.
        $html = (string) $client->getResponse()->getContent();
        self::assertDoesNotMatchRegularExpression('/<(?:link|script|img)\b[^>]+(?:href|src)="https?:\/\//i', $html);
        self::assertStringNotContainsString('api.iconify.design', $html);
        self::assertStringNotContainsString('cdn.jsdelivr.net', $html);
    }

    public function testSeccionesPrincipalesRenderizanIconosInlineSinCdn(): void
    {
        $client = static::createClient();

        foreach (['/', '/login', '/registro'] as $ruta) {
            $crawler = $client->request('GET', $ruta);
            self::assertResponseIsSuccessful();
            self::assertGreaterThan(1, $crawler->filter('svg')->count(), "La ruta {$ruta} debe renderizar múltiples íconos SVG inline.");

            $html = (string) $client->getResponse()->getContent();
            self::assertDoesNotMatchRegularExpression('/<(?:link|script|img)\b[^>]+(?:href|src)="https?:\/\//i', $html);
        }
    }

    public function testIconoNoImportadoFallaSinDescargaRemota(): void
    {
        static::createClient();

        /** @var IconRendererInterface $renderer */
        $renderer = static::getContainer()->get(IconRendererInterface::class);

        // Garantiza que iconify.on_demand=false impide descargar íconos externos en runtime.
        $this->expectException(IconNotFoundException::class);
        $renderer->renderIcon('bi:house-door');
    }
}
