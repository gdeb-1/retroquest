<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260527121520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exchange_collection_item (exchange_id INT NOT NULL, collection_item_id INT NOT NULL, INDEX IDX_584E034068AFD1A0 (exchange_id), INDEX IDX_584E03404643208F (collection_item_id), PRIMARY KEY (exchange_id, collection_item_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE exchange_collection_item ADD CONSTRAINT FK_584E034068AFD1A0 FOREIGN KEY (exchange_id) REFERENCES exchange (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exchange_collection_item ADD CONSTRAINT FK_584E03404643208F FOREIGN KEY (collection_item_id) REFERENCES collection_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collection_item ADD collector_id INT NOT NULL, ADD game_id INT NOT NULL');
        $this->addSql('ALTER TABLE collection_item ADD CONSTRAINT FK_556C09F0670BAFFE FOREIGN KEY (collector_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE collection_item ADD CONSTRAINT FK_556C09F0E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('CREATE INDEX IDX_556C09F0670BAFFE ON collection_item (collector_id)');
        $this->addSql('CREATE INDEX IDX_556C09F0E48FD905 ON collection_item (game_id)');
        $this->addSql('ALTER TABLE exchange ADD proposer_id INT NOT NULL, ADD receiver_id INT NOT NULL');
        $this->addSql('ALTER TABLE exchange ADD CONSTRAINT FK_D33BB079B13FA634 FOREIGN KEY (proposer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE exchange ADD CONSTRAINT FK_D33BB079CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_D33BB079B13FA634 ON exchange (proposer_id)');
        $this->addSql('CREATE INDEX IDX_D33BB079CD53EDB6 ON exchange (receiver_id)');
        $this->addSql('ALTER TABLE review ADD author_id INT NOT NULL, ADD game_id INT NOT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('CREATE INDEX IDX_794381C6F675F31B ON review (author_id)');
        $this->addSql('CREATE INDEX IDX_794381C6E48FD905 ON review (game_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exchange_collection_item DROP FOREIGN KEY FK_584E034068AFD1A0');
        $this->addSql('ALTER TABLE exchange_collection_item DROP FOREIGN KEY FK_584E03404643208F');
        $this->addSql('DROP TABLE exchange_collection_item');
        $this->addSql('ALTER TABLE collection_item DROP FOREIGN KEY FK_556C09F0670BAFFE');
        $this->addSql('ALTER TABLE collection_item DROP FOREIGN KEY FK_556C09F0E48FD905');
        $this->addSql('DROP INDEX IDX_556C09F0670BAFFE ON collection_item');
        $this->addSql('DROP INDEX IDX_556C09F0E48FD905 ON collection_item');
        $this->addSql('ALTER TABLE collection_item DROP collector_id, DROP game_id');
        $this->addSql('ALTER TABLE exchange DROP FOREIGN KEY FK_D33BB079B13FA634');
        $this->addSql('ALTER TABLE exchange DROP FOREIGN KEY FK_D33BB079CD53EDB6');
        $this->addSql('DROP INDEX IDX_D33BB079B13FA634 ON exchange');
        $this->addSql('DROP INDEX IDX_D33BB079CD53EDB6 ON exchange');
        $this->addSql('ALTER TABLE exchange DROP proposer_id, DROP receiver_id');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6F675F31B');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C6E48FD905');
        $this->addSql('DROP INDEX IDX_794381C6F675F31B ON review');
        $this->addSql('DROP INDEX IDX_794381C6E48FD905 ON review');
        $this->addSql('ALTER TABLE review DROP author_id, DROP game_id');
    }
}
