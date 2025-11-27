<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127003245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Сначала добавляем колонку как nullable
        $this->addSql('ALTER TABLE good ADD user_id INT');
        
        // Назначаем существующие товары первому пользователю
        $this->addSql('UPDATE good SET user_id = (SELECT id FROM "user" ORDER BY id LIMIT 1) WHERE user_id IS NULL');
        
        // Теперь делаем колонку NOT NULL
        $this->addSql('ALTER TABLE good ALTER COLUMN user_id SET NOT NULL');
        
        // Добавляем внешний ключ
        $this->addSql('ALTER TABLE good ADD CONSTRAINT FK_6C844E92A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        
        // Создаем индекс для производительности
        $this->addSql('CREATE INDEX IDX_6C844E92A76ED395 ON good (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE good DROP CONSTRAINT FK_6C844E92A76ED395');
        $this->addSql('DROP INDEX IDX_6C844E92A76ED395');
        $this->addSql('ALTER TABLE good DROP user_id');
    }
}
