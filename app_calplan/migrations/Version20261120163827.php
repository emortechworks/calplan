<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261120163827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tienda (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(120) NOT NULL, codigo VARCHAR(30) DEFAULT NULL, activa TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, usuario_id INT NOT NULL, UNIQUE INDEX UNIQ_TIENDA_NOMBRE (usuario_id, nombre), UNIQUE INDEX UNIQ_TIENDA_CODIGO (usuario_id, codigo), INDEX IDX_C0C6E53EDB38439E (usuario_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE tienda ADD CONSTRAINT FK_C0C6E53EDB38439E FOREIGN KEY (usuario_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tienda DROP FOREIGN KEY FK_C0C6E53EDB38439E');
        $this->addSql('DROP TABLE tienda');
    }
}
