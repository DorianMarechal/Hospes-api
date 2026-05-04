<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260429210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'V3 Phase 18: CHECK constraints, indexes, UNIQUE, EXCLUDE, cascades, enum column lengths, new columns';
    }

    public function up(Schema $schema): void
    {
        // CHECK constraints
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT chk_booking_total_price CHECK (total_price >= 0)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT chk_booking_nights_total CHECK (nights_total >= 0)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT chk_booking_guests CHECK (guests_count > 0)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT chk_booking_dates CHECK (checkin < checkout)');
        $this->addSql('ALTER TABLE booking_night ADD CONSTRAINT chk_booking_night_price CHECK (price >= 0)');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT chk_season_dates CHECK (start_date < end_date)');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT chk_season_base_price CHECK (price_week >= 0 AND price_weekend >= 0)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT chk_review_rating CHECK (rating >= 1 AND rating <= 5)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT chk_payment_amount CHECK (amount >= 0)');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT chk_deposit_amount CHECK (amount >= 0)');
        $this->addSql('ALTER TABLE deposit ADD CONSTRAINT chk_deposit_retained CHECK (retained_amount IS NULL OR retained_amount <= amount)');
        $this->addSql('ALTER TABLE lodging ADD CONSTRAINT chk_lodging_capacity CHECK (capacity > 0)');
        $this->addSql('ALTER TABLE lodging ADD CONSTRAINT chk_lodging_prices CHECK (base_price_week >= 0 AND base_price_weekend >= 0)');
        $this->addSql('ALTER TABLE price_override ADD CONSTRAINT chk_price_override_amount CHECK (price >= 0)');
        $this->addSql('ALTER TABLE blocked_date ADD CONSTRAINT chk_blocked_date_dates CHECK (start_date < end_date)');

        // Performance indexes
        $this->addSql('CREATE INDEX idx_lodging_city_lower ON lodging (LOWER(city))');
        $this->addSql('CREATE INDEX idx_lodging_active ON lodging (is_active)');
        $this->addSql('CREATE INDEX idx_booking_lodging_status ON booking (lodging_id, status)');
        $this->addSql('CREATE INDEX idx_booking_customer_created ON booking (customer_id, created_at DESC)');
        $this->addSql('CREATE INDEX idx_review_lodging_created ON review (lodging_id, created_at DESC)');
        $this->addSql('CREATE INDEX idx_conversation_customer ON conversation (customer_id)');
        $this->addSql('CREATE INDEX idx_conversation_host ON conversation (host_id)');
        $this->addSql('CREATE INDEX idx_message_conversation_created ON message (conversation_id, created_at DESC)');

        // UNIQUE constraint on staff_permission
        $this->addSql('CREATE UNIQUE INDEX uniq_staff_permission ON staff_permission (staff_assignment_id, permission)');

        // EXCLUDE constraint on blocked_date (no overlap per lodging)
        $this->addSql('ALTER TABLE blocked_date ADD CONSTRAINT excl_blocked_date_no_overlap EXCLUDE USING gist (lodging_id WITH =, daterange(start_date, end_date) WITH &&)');

        // ON DELETE CASCADE for child tables
        $this->addSql('ALTER TABLE blocked_date DROP CONSTRAINT IF EXISTS fk_blocked_date_lodging');
        $this->addSql('ALTER TABLE blocked_date ADD CONSTRAINT fk_blocked_date_lodging FOREIGN KEY (lodging_id) REFERENCES lodging(id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE blocked_date VALIDATE CONSTRAINT fk_blocked_date_lodging');

        $this->addSql('ALTER TABLE season DROP CONSTRAINT IF EXISTS fk_season_lodging');
        $this->addSql('ALTER TABLE season ADD CONSTRAINT fk_season_lodging FOREIGN KEY (lodging_id) REFERENCES lodging(id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE season VALIDATE CONSTRAINT fk_season_lodging');

        $this->addSql('ALTER TABLE price_override DROP CONSTRAINT IF EXISTS fk_price_override_lodging');
        $this->addSql('ALTER TABLE price_override ADD CONSTRAINT fk_price_override_lodging FOREIGN KEY (lodging_id) REFERENCES lodging(id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE price_override VALIDATE CONSTRAINT fk_price_override_lodging');

        $this->addSql('ALTER TABLE host_legal_identifier DROP CONSTRAINT IF EXISTS fk_host_legal_identifier_host_profile');
        $this->addSql('ALTER TABLE host_legal_identifier ADD CONSTRAINT fk_host_legal_identifier_host_profile FOREIGN KEY (host_profile_id) REFERENCES host_profile(id) ON DELETE CASCADE NOT VALID');
        $this->addSql('ALTER TABLE host_legal_identifier VALIDATE CONSTRAINT fk_host_legal_identifier_host_profile');

        // New columns on review
        $this->addSql('ALTER TABLE review ADD COLUMN host_response_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD COLUMN moderated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD COLUMN moderated_by UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT fk_review_moderated_by FOREIGN KEY (moderated_by) REFERENCES "user"(id) ON DELETE SET NULL');

        // New column on payment: original_payment_id for refund tracking
        $this->addSql('ALTER TABLE payment ADD COLUMN original_payment_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT fk_payment_original FOREIGN KEY (original_payment_id) REFERENCES payment(id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_payment_original ON payment (original_payment_id)');

        // New column on booking: source tracking
        $this->addSql('ALTER TABLE booking ADD COLUMN source VARCHAR(30) DEFAULT \'direct\' NOT NULL');

        // VARCHAR(255) → real length for enum columns
        $this->addSql('ALTER TABLE lodging ALTER COLUMN type TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE lodging ALTER COLUMN cancellation_policy TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE booking ALTER COLUMN cancellation_policy TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE booking ALTER COLUMN status TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE booking_modification_request ALTER COLUMN status TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE conversation ALTER COLUMN status TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE host_profile ALTER COLUMN payment_provider TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE payment ALTER COLUMN type TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE payment ALTER COLUMN method TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE payment ALTER COLUMN status TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE deposit ALTER COLUMN status TYPE VARCHAR(25)');
        $this->addSql('ALTER TABLE booking_status_history ALTER COLUMN previous_status TYPE VARCHAR(20)');
        $this->addSql('ALTER TABLE booking_status_history ALTER COLUMN new_status TYPE VARCHAR(20)');

        // PostGIS: geography column + GiST index on lodging
        $this->addSql('ALTER TABLE lodging ADD COLUMN location geography(Point, 4326) DEFAULT NULL');
        $this->addSql('UPDATE lodging SET location = ST_SetSRID(ST_MakePoint(longitude::float, latitude::float), 4326)::geography WHERE latitude IS NOT NULL AND longitude IS NOT NULL');
        $this->addSql('CREATE INDEX idx_lodging_location_gist ON lodging USING gist (location)');
    }

    public function down(Schema $schema): void
    {
        // PostGIS
        $this->addSql('DROP INDEX IF EXISTS idx_lodging_location_gist');
        $this->addSql('ALTER TABLE lodging DROP COLUMN IF EXISTS location');

        // booking.source
        $this->addSql('ALTER TABLE booking DROP COLUMN IF EXISTS source');

        // payment.original_payment_id
        $this->addSql('DROP INDEX IF EXISTS idx_payment_original');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT IF EXISTS fk_payment_original');
        $this->addSql('ALTER TABLE payment DROP COLUMN IF EXISTS original_payment_id');

        // review columns
        $this->addSql('ALTER TABLE review DROP CONSTRAINT IF EXISTS fk_review_moderated_by');
        $this->addSql('ALTER TABLE review DROP COLUMN IF EXISTS moderated_by');
        $this->addSql('ALTER TABLE review DROP COLUMN IF EXISTS moderated_at');
        $this->addSql('ALTER TABLE review DROP COLUMN IF EXISTS host_response_at');

        // Cascades (revert to default)
        $this->addSql('ALTER TABLE host_legal_identifier DROP CONSTRAINT IF EXISTS fk_host_legal_identifier_host_profile');
        $this->addSql('ALTER TABLE price_override DROP CONSTRAINT IF EXISTS fk_price_override_lodging');
        $this->addSql('ALTER TABLE season DROP CONSTRAINT IF EXISTS fk_season_lodging');
        $this->addSql('ALTER TABLE blocked_date DROP CONSTRAINT IF EXISTS fk_blocked_date_lodging');

        // EXCLUDE + UNIQUE
        $this->addSql('ALTER TABLE blocked_date DROP CONSTRAINT IF EXISTS excl_blocked_date_no_overlap');
        $this->addSql('DROP INDEX IF EXISTS uniq_staff_permission');

        // Indexes
        $this->addSql('DROP INDEX IF EXISTS idx_message_conversation_created');
        $this->addSql('DROP INDEX IF EXISTS idx_conversation_host');
        $this->addSql('DROP INDEX IF EXISTS idx_conversation_customer');
        $this->addSql('DROP INDEX IF EXISTS idx_review_lodging_created');
        $this->addSql('DROP INDEX IF EXISTS idx_booking_customer_created');
        $this->addSql('DROP INDEX IF EXISTS idx_booking_lodging_status');
        $this->addSql('DROP INDEX IF EXISTS idx_lodging_active');
        $this->addSql('DROP INDEX IF EXISTS idx_lodging_city_lower');

        // CHECK constraints
        $this->addSql('ALTER TABLE blocked_date DROP CONSTRAINT IF EXISTS chk_blocked_date_dates');
        $this->addSql('ALTER TABLE price_override DROP CONSTRAINT IF EXISTS chk_price_override_amount');
        $this->addSql('ALTER TABLE lodging DROP CONSTRAINT IF EXISTS chk_lodging_prices');
        $this->addSql('ALTER TABLE lodging DROP CONSTRAINT IF EXISTS chk_lodging_capacity');
        $this->addSql('ALTER TABLE deposit DROP CONSTRAINT IF EXISTS chk_deposit_retained');
        $this->addSql('ALTER TABLE deposit DROP CONSTRAINT IF EXISTS chk_deposit_amount');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT IF EXISTS chk_payment_amount');
        $this->addSql('ALTER TABLE review DROP CONSTRAINT IF EXISTS chk_review_rating');
        $this->addSql('ALTER TABLE season DROP CONSTRAINT IF EXISTS chk_season_base_price');
        $this->addSql('ALTER TABLE season DROP CONSTRAINT IF EXISTS chk_season_dates');
        $this->addSql('ALTER TABLE booking_night DROP CONSTRAINT IF EXISTS chk_booking_night_price');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT IF EXISTS chk_booking_dates');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT IF EXISTS chk_booking_guests');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT IF EXISTS chk_booking_nights_total');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT IF EXISTS chk_booking_total_price');
    }
}
