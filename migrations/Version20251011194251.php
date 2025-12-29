<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251011194251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exercise_sets (id UUID NOT NULL, workout_exercise_id UUID NOT NULL, sets_count INT NOT NULL, reps INT NOT NULL, weight_grams INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_exercise_sets_workout_exercise_id ON exercise_sets (workout_exercise_id)');
        $this->addSql('COMMENT ON COLUMN exercise_sets.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN exercise_sets.workout_exercise_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN exercise_sets.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE exercises (id UUID NOT NULL, muscle_category_id UUID NOT NULL, name VARCHAR(255) NOT NULL, name_en VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_exercises_muscle_category_id ON exercises (muscle_category_id)');
        $this->addSql('CREATE UNIQUE INDEX idx_exercises_name ON exercises (name)');
        $this->addSql('COMMENT ON COLUMN exercises.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN exercises.muscle_category_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN exercises.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN exercises.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE muscle_categories (id UUID NOT NULL, name_pl VARCHAR(100) NOT NULL, name_en VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX idx_muscle_categories_name_pl ON muscle_categories (name_pl)');
        $this->addSql('CREATE UNIQUE INDEX idx_muscle_categories_name_en ON muscle_categories (name_en)');
        $this->addSql('COMMENT ON COLUMN muscle_categories.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN muscle_categories.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_users_email_lower ON users (email)');
        $this->addSql('COMMENT ON COLUMN users.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN users.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN users.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE workout_exercises (id UUID NOT NULL, workout_session_id UUID NOT NULL, exercise_id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_workout_exercises_workout_session_id ON workout_exercises (workout_session_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_workout_exercises_exercise_id ON workout_exercises (exercise_id)');
        $this->addSql('COMMENT ON COLUMN workout_exercises.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_exercises.workout_session_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_exercises.exercise_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_exercises.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE workout_sessions (id UUID NOT NULL, user_id UUID NOT NULL, deleted_by UUID DEFAULT NULL, date DATE NOT NULL, name VARCHAR(255) DEFAULT NULL, notes TEXT DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_421170A5A76ED395 ON workout_sessions (user_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_workout_sessions_user_id_date ON workout_sessions (user_id, date)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_workout_sessions_deleted_by ON workout_sessions (deleted_by)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_workout_sessions_active ON workout_sessions (user_id, date)');
        $this->addSql('COMMENT ON COLUMN workout_sessions.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_sessions.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_sessions.deleted_by IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN workout_sessions.deleted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN workout_sessions.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN workout_sessions.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE exercise_sets ADD CONSTRAINT FK_CDA82763E435DB6B FOREIGN KEY (workout_exercise_id) REFERENCES workout_exercises (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE exercises ADD CONSTRAINT FK_FA14991C4565C42 FOREIGN KEY (muscle_category_id) REFERENCES muscle_categories (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_exercises ADD CONSTRAINT FK_2D7B2EC5D1BA355 FOREIGN KEY (workout_session_id) REFERENCES workout_sessions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_exercises ADD CONSTRAINT FK_2D7B2EC5E934951A FOREIGN KEY (exercise_id) REFERENCES exercises (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_sessions ADD CONSTRAINT FK_421170A5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE workout_sessions ADD CONSTRAINT FK_421170A51F6FA0AF FOREIGN KEY (deleted_by) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE exercise_sets DROP CONSTRAINT FK_CDA82763E435DB6B');
        $this->addSql('ALTER TABLE exercises DROP CONSTRAINT FK_FA14991C4565C42');
        $this->addSql('ALTER TABLE workout_exercises DROP CONSTRAINT FK_2D7B2EC5D1BA355');
        $this->addSql('ALTER TABLE workout_exercises DROP CONSTRAINT FK_2D7B2EC5E934951A');
        $this->addSql('ALTER TABLE workout_sessions DROP CONSTRAINT FK_421170A5A76ED395');
        $this->addSql('ALTER TABLE workout_sessions DROP CONSTRAINT FK_421170A51F6FA0AF');
        $this->addSql('DROP TABLE exercise_sets');
        $this->addSql('DROP TABLE exercises');
        $this->addSql('DROP TABLE muscle_categories');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE workout_exercises');
        $this->addSql('DROP TABLE workout_sessions');
    }
}
