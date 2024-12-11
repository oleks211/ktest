<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241210180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("INSERT INTO user (name, phone_number, email, created_at) VALUES ('John Doe', '+12345678901', 'john.doe@example.com', '2024-12-11 12:00:00')");
        $this->addSql("INSERT INTO user (name, phone_number, email, created_at) VALUES ('Jane Smith', '+19876543210', 'jane.smith@example.com', '2024-12-11 12:05:00')");
        $this->addSql("INSERT INTO user (name, phone_number, email, created_at) VALUES ('Alice Johnson', '+11234567890', 'alice.johnson@example.com', '2024-12-11 12:10:00')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("DELETE FROM user WHERE email = 'john.doe@example.com'");
        $this->addSql("DELETE FROM user WHERE email = 'jane.smith@example.com'");
        $this->addSql("DELETE FROM user WHERE email = 'alice.johnson@example.com'");
    }
}
