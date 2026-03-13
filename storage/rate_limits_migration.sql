-- Run this SQL to create the rate_limits table for the database-backed RateLimiter

CREATE TABLE IF NOT EXISTS rate_limits (
    id SERIAL PRIMARY KEY,
    key_hash VARCHAR(64) NOT NULL,
    timestamp INT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_rate_limits_key_hash ON rate_limits(key_hash);