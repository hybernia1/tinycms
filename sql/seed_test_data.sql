SET @prefix = '';

DROP PROCEDURE IF EXISTS seed_tinycms_test_data;

DELIMITER $$
CREATE PROCEDURE seed_tinycms_test_data(IN in_prefix VARCHAR(64))
BEGIN
    DECLARE pfx VARCHAR(64) DEFAULT TRIM(IFNULL(in_prefix, ''));

    IF pfx <> '' AND RIGHT(pfx, 1) <> '_' THEN
        SET pfx = CONCAT(pfx, '_');
    END IF;

    SET @users_table = CONCAT(pfx, 'users');
    SET @media_table = CONCAT(pfx, 'media');
    SET @content_table = CONCAT(pfx, 'content');
    SET @terms_table = CONCAT(pfx, 'terms');
    SET @content_terms_table = CONCAT(pfx, 'content_terms');
    SET @content_media_table = CONCAT(pfx, 'content_media');

    SET FOREIGN_KEY_CHECKS = 0;

    SET @sql = CONCAT('TRUNCATE TABLE ', @content_media_table);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT('TRUNCATE TABLE ', @content_terms_table);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT('TRUNCATE TABLE ', @content_table);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT('TRUNCATE TABLE ', @media_table);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT('TRUNCATE TABLE ', @terms_table);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT('TRUNCATE TABLE ', @users_table);
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET FOREIGN_KEY_CHECKS = 1;

    SET @sql = CONCAT(
        'INSERT INTO ', @users_table, ' (email, password, name, role, suspend, created) ',
        'WITH RECURSIVE seq AS (',
        '    SELECT 1 AS n ',
        '    UNION ALL ',
        '    SELECT n + 1 FROM seq WHERE n < 50',
        ') ',
        'SELECT ',
        '    CONCAT("user", n, "@example.test"), ',
        '    "$2y$12$oE7lS6Gh2T6mhHBUz.1gwufaf9GZPZh.3D4AVN4YNpxyDfaQJOJ4S", ',
        '    CONCAT("Test User ", LPAD(n, 2, "0")), ',
        '    CASE WHEN n = 1 THEN "admin" ELSE "user" END, ',
        '    0, ',
        '    DATE_SUB(NOW(), INTERVAL (50 - n) DAY) ',
        'FROM seq'
    );
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT(
        'INSERT INTO ', @terms_table, ' (name, created) ',
        'WITH RECURSIVE seq AS (',
        '    SELECT 1 AS n ',
        '    UNION ALL ',
        '    SELECT n + 1 FROM seq WHERE n < 100',
        ') ',
        'SELECT CONCAT("Tag ", LPAD(n, 3, "0")), DATE_SUB(NOW(), INTERVAL MOD(n * 2, 200) DAY) FROM seq'
    );
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT(
        'INSERT INTO ', @media_table, ' (author, path, name, created) ',
        'WITH RECURSIVE seq AS (',
        '    SELECT 1 AS n ',
        '    UNION ALL ',
        '    SELECT n + 1 FROM seq WHERE n < 100',
        ') ',
        'SELECT ',
        '    1 + MOD(n - 1, 50), ',
        '    CONCAT("/uploads/media-", LPAD(n, 3, "0"), ".jpg"), ',
        '    CONCAT("Media ", LPAD(n, 3, "0")), ',
        '    DATE_SUB(NOW(), INTERVAL MOD(n * 5, 300) DAY) ',
        'FROM seq'
    );
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT(
        'INSERT INTO ', @content_table, ' (author, status, created, body, excerpt, name, thumbnail) ',
        'WITH RECURSIVE seq AS (',
        '    SELECT 1 AS n ',
        '    UNION ALL ',
        '    SELECT n + 1 FROM seq WHERE n < 100',
        ') ',
        'SELECT ',
        '    1 + MOD(n - 1, 50), ',
        '    CASE WHEN MOD(n, 10) < 7 THEN "published" WHEN MOD(n, 10) < 9 THEN "draft" ELSE "trash" END, ',
        '    DATE_SUB(NOW(), INTERVAL MOD(n * 3, 365) DAY), ',
        '    CONCAT(',
        '        "<p>", ELT(1 + MOD(CRC32(CONCAT("p1-", n)), 10), "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", "Praesent varius lacus vel sem tincidunt, quis aliquam arcu suscipit.", "Vestibulum ante ipsum primis in faucibus orci luctus et ultrices.", "Integer pulvinar justo id sapien tincidunt, nec faucibus mauris posuere.", "Donec eget risus nec dui ultricies pellentesque.", "Aliquam erat volutpat, sed fermentum nibh non lectus.", "Curabitur blandit tortor non diam sollicitudin, vitae accumsan eros tempor.", "Phasellus dignissim purus ac massa varius, at pretium lacus viverra.", "Suspendisse potenti, in tincidunt odio et urna placerat eleifend.", "Mauris gravida ligula non elit luctus, vitae vulputate libero interdum."), "</p>", ',
        '        "<p>", ELT(1 + MOD(CRC32(CONCAT("p2-", n)), 10), "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", "Praesent varius lacus vel sem tincidunt, quis aliquam arcu suscipit.", "Vestibulum ante ipsum primis in faucibus orci luctus et ultrices.", "Integer pulvinar justo id sapien tincidunt, nec faucibus mauris posuere.", "Donec eget risus nec dui ultricies pellentesque.", "Aliquam erat volutpat, sed fermentum nibh non lectus.", "Curabitur blandit tortor non diam sollicitudin, vitae accumsan eros tempor.", "Phasellus dignissim purus ac massa varius, at pretium lacus viverra.", "Suspendisse potenti, in tincidunt odio et urna placerat eleifend.", "Mauris gravida ligula non elit luctus, vitae vulputate libero interdum."), "</p>", ',
        '        "<p>", ELT(1 + MOD(CRC32(CONCAT("p3-", n)), 10), "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", "Praesent varius lacus vel sem tincidunt, quis aliquam arcu suscipit.", "Vestibulum ante ipsum primis in faucibus orci luctus et ultrices.", "Integer pulvinar justo id sapien tincidunt, nec faucibus mauris posuere.", "Donec eget risus nec dui ultricies pellentesque.", "Aliquam erat volutpat, sed fermentum nibh non lectus.", "Curabitur blandit tortor non diam sollicitudin, vitae accumsan eros tempor.", "Phasellus dignissim purus ac massa varius, at pretium lacus viverra.", "Suspendisse potenti, in tincidunt odio et urna placerat eleifend.", "Mauris gravida ligula non elit luctus, vitae vulputate libero interdum."), "</p>", ',
        '        "<p>", ELT(1 + MOD(CRC32(CONCAT("p4-", n)), 10), "Lorem ipsum dolor sit amet, consectetur adipiscing elit.", "Praesent varius lacus vel sem tincidunt, quis aliquam arcu suscipit.", "Vestibulum ante ipsum primis in faucibus orci luctus et ultrices.", "Integer pulvinar justo id sapien tincidunt, nec faucibus mauris posuere.", "Donec eget risus nec dui ultricies pellentesque.", "Aliquam erat volutpat, sed fermentum nibh non lectus.", "Curabitur blandit tortor non diam sollicitudin, vitae accumsan eros tempor.", "Phasellus dignissim purus ac massa varius, at pretium lacus viverra.", "Suspendisse potenti, in tincidunt odio et urna placerat eleifend.", "Mauris gravida ligula non elit luctus, vitae vulputate libero interdum."), "</p>"',
        '    ), ',
        '    CONCAT("Excerpt pro článek ", n), ',
        '    CONCAT("Test článek ", LPAD(n, 3, "0")), ',
        '    1 + MOD((n * 7) - 1, 100) ',
        'FROM seq'
    );
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT(
        'INSERT INTO ', @content_terms_table, ' (content, term) ',
        'WITH RECURSIVE seq AS (',
        '    SELECT 1 AS n ',
        '    UNION ALL ',
        '    SELECT n + 1 FROM seq WHERE n < 100',
        ') ',
        'SELECT n, 1 + MOD((n * 3) - 1, 100) FROM seq ',
        'UNION ALL SELECT n, 1 + MOD((n * 3 + 11) - 1, 100) FROM seq ',
        'UNION ALL SELECT n, 1 + MOD((n * 3 + 23) - 1, 100) FROM seq'
    );
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = CONCAT(
        'INSERT INTO ', @content_media_table, ' (content, media) ',
        'WITH RECURSIVE seq AS (',
        '    SELECT 1 AS n ',
        '    UNION ALL ',
        '    SELECT n + 1 FROM seq WHERE n < 100',
        ') ',
        'SELECT n, 1 + MOD((n * 5) - 1, 100) FROM seq ',
        'UNION ALL SELECT n, 1 + MOD((n * 5 + 37) - 1, 100) FROM seq'
    );
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
END$$
DELIMITER ;

CALL seed_tinycms_test_data(@prefix);
DROP PROCEDURE seed_tinycms_test_data;
