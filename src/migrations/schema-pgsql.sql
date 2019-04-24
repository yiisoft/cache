/**
 * Database schema required by \Yiisoft\Cache\DbCache.
 *
 * @link http://www.yiiframework.com/
 * @copyright 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

drop table if exists "cache";

create table "cache"
(
    "id"  varchar(128) not null,
    "expire" integer,
    "data"   bytea,
    primary key ("id")
);
