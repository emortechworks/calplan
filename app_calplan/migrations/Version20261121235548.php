<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261121235548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rol (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(120) NOT NULL, activo TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, usuario_id INT NOT NULL, UNIQUE INDEX UNIQ_ROL_NOMBRE (usuario_id, nombre), INDEX IDX_E553F37DB38439E (usuario_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE rol ADD CONSTRAINT FK_E553F37DB38439E FOREIGN KEY (usuario_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rol DROP FOREIGN KEY FK_E553F37DB38439E');
        $this->addSql('DROP TABLE rol');
    }
}
