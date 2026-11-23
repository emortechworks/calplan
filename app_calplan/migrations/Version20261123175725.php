<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261123175725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE atributo_perfil (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(80) NOT NULL, tipo VARCHAR(20) NOT NULL, opciones JSON DEFAULT NULL, orden SMALLINT DEFAULT 0 NOT NULL, activo TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, usuario_id INT NOT NULL, UNIQUE INDEX UNIQ_ATRIBUTO_PERFIL_USUARIO_NOMBRE (usuario_id, nombre), INDEX IDX_FEE031B5DB38439E (usuario_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE valor_perfil (id INT AUTO_INCREMENT NOT NULL, valor VARCHAR(255) NOT NULL, updated_at DATETIME NOT NULL, personal_id INT NOT NULL, atributo_id INT NOT NULL, UNIQUE INDEX UNIQ_VALOR_PERFIL_PERSONAL_ATRIBUTO (personal_id, atributo_id), INDEX IDX_75B6D95F5D430949 (personal_id), INDEX IDX_75B6D95FA55FF1F3 (atributo_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE atributo_perfil ADD CONSTRAINT FK_FEE031B5DB38439E FOREIGN KEY (usuario_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE valor_perfil ADD CONSTRAINT FK_75B6D95F5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE valor_perfil ADD CONSTRAINT FK_75B6D95FA55FF1F3 FOREIGN KEY (atributo_id) REFERENCES atributo_perfil (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE atributo_perfil DROP FOREIGN KEY FK_FEE031B5DB38439E');
        $this->addSql('ALTER TABLE valor_perfil DROP FOREIGN KEY FK_75B6D95F5D430949');
        $this->addSql('ALTER TABLE valor_perfil DROP FOREIGN KEY FK_75B6D95FA55FF1F3');
        $this->addSql('DROP TABLE atributo_perfil');
        $this->addSql('DROP TABLE valor_perfil');
    }
}
