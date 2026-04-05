<?php
define("ADMIN_EMAIL", getenv("ADMIN_EMAIL") ?: "silviaadmin@gmail.com");
define("APP_TOKEN_SECRET", getenv("APP_TOKEN_SECRET") ?: "change_this_to_a_long_random_secret");

// SMTP SETTINGS
define("SMTP_HOST", getenv("SMTP_HOST") ?: "smtp.gmail.com");
define("SMTP_PORT", getenv("SMTP_PORT") ?: 587);
define("SMTP_USER", getenv("SMTP_USER") ?: "mujaddedc@gmail.com");
define("SMTP_PASS", getenv("SMTP_PASS") ?: "jmzj ixyf kpux zmxl");
define("SMTP_FROM", getenv("SMTP_FROM") ?: "mujaddedc@gmail.com");
define("SMTP_FROM_NAME", getenv("SMTP_FROM_NAME") ?: "WarmConnect");