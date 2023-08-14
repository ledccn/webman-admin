CREATE TABLE `wa_admins`
(
    `id`         INTEGER NOT NULL PRIMARY KEY autoincrement,
    `username`   varchar(32)  default NULL,
    `nickname`   varchar(40)  default NULL,
    `password`   varchar(255) default NULL,
    `avatar`     varchar(255) DEFAULT '/app/admin/avatar.png',
    `email`      varchar(100) DEFAULT NULL,
    `mobile`     varchar(16)  DEFAULT NULL,
    `created_at` datetime     DEFAULT NULL,
    `updated_at` datetime     DEFAULT NULL,
    `login_at`   datetime     DEFAULT NULL,
    `status`     tinyint(4)   DEFAULT NULL
);
CREATE UNIQUE INDEX wa_admins_username ON wa_admins ("username");

CREATE TABLE IF NOT EXISTS `wa_admin_roles`
(
    `id`       INTEGER NOT NULL PRIMARY KEY autoincrement,
    `role_id`  int(11) NOT NULL,
    `admin_id` int(11) NOT NULL
);
CREATE UNIQUE INDEX role_admin_id ON wa_admin_roles ("role_id", "admin_id");

CREATE TABLE IF NOT EXISTS `wa_options`
(
    `id`         INTEGER      NOT NULL PRIMARY KEY autoincrement,
    `name`       varchar(255) NOT NULL,
    `value`      longtext     NOT NULL,
    `created_at` datetime     NOT NULL DEFAULT '2022-08-15 00:00:00',
    `updated_at` datetime     NOT NULL DEFAULT '2022-08-15 00:00:00'
);

INSERT INTO `wa_options` (id, name, value, created_at, updated_at)
VALUES (1, 'system_config',
        '{"logo":{"title":"Webman Admin","image":"/app/admin/admin/images/logo.png"},"menu":{"data":"/app/admin/rule/get","method":"GET","accordion":true,"collapse":false,"control":false,"controlWidth":500,"select":"0","async":true},"tab":{"enable":true,"keepState":true,"session":true,"preload":false,"max":"30","index":{"id":"0","href":"/app/admin/index/dashboard","title":"仪表盘"}},"theme":{"defaultColor":"2","defaultMenu":"light-theme","defaultHeader":"light-theme","allowCustom":true,"banner":false},"colors":[{"id":"1","color":"#36b368","second":"#f0f9eb"},{"id":"2","color":"#2d8cf0","second":"#ecf5ff"},{"id":"3","color":"#f6ad55","second":"#fdf6ec"},{"id":"4","color":"#f56c6c","second":"#fef0f0"},{"id":"5","color":"#3963bc","second":"#ecf5ff"}],"other":{"keepLoad":"500","autoHead":false,"footer":false},"header":{"message":false}}',
        '2023-08-12 00:26:29', '2023-08-12 00:26:29');

CREATE TABLE IF NOT EXISTS `wa_roles`
(
    `id`         INTEGER     NOT NULL PRIMARY KEY autoincrement,
    `name`       varchar(80) NOT NULL,
    `rules`      text,
    `created_at` datetime    NOT NULL,
    `updated_at` datetime    NOT NULL,
    `pid`        int(10) DEFAULT NULL
);

INSERT INTO `wa_roles`
VALUES (1, '超级管理员', '*', '2022-08-13 16:15:01', '2022-12-23 12:05:07', NULL);

CREATE TABLE `wa_rules`
(
    `id`         INTEGER NOT NULL PRIMARY KEY autoincrement,
    `title`      varchar(255) default NULL,
    `icon`       varchar(255) default NULL,
    `key`        varchar(255) default NULL,
    `pid`        int(10)      DEFAULT '0',
    `created_at` datetime     default NULL,
    `updated_at` datetime     default NULL,
    `href`       varchar(255) default NULL,
    `type`       int(11)      DEFAULT '1',
    `weight`     int(11)      DEFAULT '0'
);
CREATE UNIQUE INDEX wa_rules_key ON wa_rules ("key");

CREATE TABLE IF NOT EXISTS `wa_uploads`
(
    `id`         INTEGER NOT NULL PRIMARY KEY autoincrement,
    `name`         varchar(128) NOT NULL,
    `url`          varchar(255) NOT NULL,
    `admin_id`     int(11)               DEFAULT NULL,
    `file_size`    int(11)      NOT NULL,
    `mime_type`    varchar(255) NOT NULL,
    `image_width`  int(11)               DEFAULT NULL,
    `image_height` int(11)               DEFAULT NULL,
    `ext`          varchar(128) NOT NULL,
    `storage`      varchar(255) NOT NULL DEFAULT 'local',
    `created_at`   date                  DEFAULT NULL,
    `category`     varchar(128)          DEFAULT NULL,
    `updated_at`   date                  DEFAULT NULL
);
CREATE INDEX wa_uploads_category ON wa_uploads ("category");
CREATE INDEX wa_uploads_admin_id ON wa_uploads ("admin_id");
CREATE INDEX wa_uploads_name ON wa_uploads ("name");
CREATE INDEX wa_uploads_ext ON wa_uploads ("ext");

CREATE TABLE IF NOT EXISTS `wa_users`
(
    `id`         INTEGER NOT NULL PRIMARY KEY autoincrement,
    `username`   varchar(32)      NOT NULL,
    `nickname`   varchar(40)      NOT NULL,
    `password`   varchar(255)     NOT NULL,
    `sex`        tinyint(4)   NOT NULL DEFAULT '0',
    `avatar`     varchar(255)              DEFAULT NULL,
    `email`      varchar(128)              DEFAULT NULL,
    `mobile`     varchar(16)               DEFAULT NULL,
    `level`      tinyint(4)       NOT NULL DEFAULT '0',
    `birthday`   date                      DEFAULT NULL,
    `money`      decimal(10, 2)   NOT NULL DEFAULT '0.00',
    `score`      int(11)          NOT NULL DEFAULT '0',
    `last_time`  datetime                  DEFAULT NULL,
    `last_ip`    varchar(50)               DEFAULT NULL,
    `join_time`  datetime                  DEFAULT NULL,
    `join_ip`    varchar(50)               DEFAULT NULL,
    `token`      varchar(50)               DEFAULT NULL,
    `created_at` datetime                  DEFAULT NULL,
    `updated_at` datetime                  DEFAULT NULL,
    `role`       int(11)          NOT NULL DEFAULT '1',
    `status`     tinyint(4)       NOT NULL DEFAULT '0');
CREATE UNIQUE INDEX wa_users_username ON wa_users ("username");
CREATE INDEX wa_users_join_time ON wa_users ("join_time");
CREATE INDEX wa_users_mobile ON wa_users ("mobile");
CREATE INDEX wa_users_name ON wa_users ("email");
