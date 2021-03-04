<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200716155718 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE author (id INT NOT NULL, email VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, chat_username VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, merge_request_id INT NOT NULL, project_id INT NOT NULL, status VARCHAR(255) NOT NULL, scope VARCHAR(255) NOT NULL, approval_count INT NOT NULL, UNIQUE INDEX UNIQ_794381C62FCB3624 (merge_request_id), INDEX IDX_794381C6166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE review_author (review_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_37D99F083E2E969B (review_id), INDEX IDX_37D99F08F675F31B (author_id), PRIMARY KEY(review_id, author_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE merge_request (id INT NOT NULL, author_id INT NOT NULL, project_id INT DEFAULT NULL, merge_status VARCHAR(255) NOT NULL, state VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_5F2666E1F675F31B (author_id), INDEX IDX_5F2666E1166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project (id INT NOT NULL, homepage VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment (id INT NOT NULL, merge_request_id INT DEFAULT NULL, project_id INT DEFAULT NULL, author_id INT NOT NULL, review_id INT DEFAULT NULL, note VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_9474526C2FCB3624 (merge_request_id), INDEX IDX_9474526C166D1F9C (project_id), INDEX IDX_9474526CF675F31B (author_id), INDEX IDX_9474526C3E2E969B (review_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C62FCB3624 FOREIGN KEY (merge_request_id) REFERENCES merge_request (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE review_author ADD CONSTRAINT FK_37D99F083E2E969B FOREIGN KEY (review_id) REFERENCES review (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE review_author ADD CONSTRAINT FK_37D99F08F675F31B FOREIGN KEY (author_id) REFERENCES author (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE merge_request ADD CONSTRAINT FK_5F2666E1F675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE merge_request ADD CONSTRAINT FK_5F2666E1166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C2FCB3624 FOREIGN KEY (merge_request_id) REFERENCES merge_request (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CF675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C3E2E969B FOREIGN KEY (review_id) REFERENCES review (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE review_author DROP FOREIGN KEY FK_37D99F08F675F31B');
        $this->addSql('ALTER TABLE merge_request DROP FOREIGN KEY FK_5F2666E1F675F31B');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CF675F31B');
        $this->addSql('ALTER TABLE review_author DROP FOREIGN KEY FK_37D99F083E2E969B');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C3E2E969B');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C62FCB3624');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C2FCB3624');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6166D1F9C');
        $this->addSql('ALTER TABLE merge_request DROP FOREIGN KEY FK_5F2666E1166D1F9C');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C166D1F9C');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE review_author');
        $this->addSql('DROP TABLE merge_request');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE comment');
    }
}
