<?php

namespace App\Tests\Ui;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class IndicadorCargaTest extends WebTestCase
{
    public function testBaseIncluyeOverlayDeCargaCentradoConStimulus(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body[data-controller="carga"]');
        self::assertSelectorExists('#indicador-carga.d-none[data-carga-target="overlay"]');
        self::assertSelectorExists('#indicador-carga [role="status"] svg');

        $html = (string) $client->getResponse()->getContent();
        self::assertDoesNotMatchRegularExpression('/<(?:link|script|img)\b[^>]+(?:href|src)="https?:\/\//i', $html);
    }
}
