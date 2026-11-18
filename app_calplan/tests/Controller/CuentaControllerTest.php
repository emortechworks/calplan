<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CuentaControllerTest extends WebTestCase
{
    public function testCuentaRedirigeSinSesion(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cuenta');

        self::assertResponseRedirects('/login');
    }

    public function testCuentaMuestraEmailConSesion(): void
    {
        $client = static::createClient();
        $email = 'test_' . uniqid() . '@example.com';

        // registra al usuario vía el flujo real HTTP; queda autenticado tras el registro
        $crawler = $client->request('GET', '/registro');
        $form = $crawler->selectButton('Crear cuenta')->form();
        $form['registration_form[email]'] = $email;
        $form['registration_form[plainPassword]'] = 'secret123';
        $form['registration_form[agreeTerms]']->tick();
        $client->submit($form);

        $client->request('GET', '/cuenta');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.card-body', $email);
    }
}
