<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistroRedirectYCuentaVisible(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/registro');
        self::assertResponseIsSuccessful();

        $email = 'test_' . uniqid() . '@example.com';
        $form = $crawler->selectButton('Crear cuenta')->form();
        $form['registration_form[email]'] = $email;
        $form['registration_form[plainPassword]'] = 'secret123';
        $form['registration_form[agreeTerms]']->tick();
        $client->submit($form);

        self::assertResponseRedirects('/cuenta');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.card-body', $email);
    }
}
