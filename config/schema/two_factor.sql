-- Two-factor sign-in for agent users (authenticator app or emailed OTP).
-- 2FA applies when agents.mobile_otp AND agent_users.two_factor_enabled are both on.
-- After running: bin/cake schema_cache clear

ALTER TABLE `agent_users`
  ADD `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 1 AFTER `otp_created`,
  ADD `two_factor_method` enum('authenticator','email_otp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'authenticator' AFTER `two_factor_enabled`,
  ADD `totp_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `two_factor_method`,
  ADD `totp_enabled` datetime DEFAULT NULL AFTER `totp_secret`,
  ADD `totp_last_step` bigint(20) DEFAULT NULL AFTER `totp_enabled`,
  ADD `backup_codes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `totp_last_step`;

-- Existing users start with 2FA on when their agent has OTP on.
UPDATE `agent_users` au
  JOIN `agents` a ON a.id = au.agent_id
  SET au.two_factor_enabled = IF(a.mobile_otp, 1, 0);
