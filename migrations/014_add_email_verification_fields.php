<?php
/**
 * Migration: Add email verification fields for email change feature
 */
return [
    "ALTER TABLE users ADD COLUMN pending_email TEXT",
    "ALTER TABLE users ADD COLUMN email_verification_token TEXT",
    "ALTER TABLE users ADD COLUMN email_verification_expires DATETIME",
];
