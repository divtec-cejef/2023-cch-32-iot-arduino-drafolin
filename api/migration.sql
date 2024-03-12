drop table if exists measures;
CREATE TABLE `measures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `temperature` double(8,2) NOT NULL,
  `humidity` double(8,2) NOT NULL,
  `measure_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
