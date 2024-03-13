DROP TABLE IF EXISTS `devices`; 
CREATE TABLE `devices` (
	`id` bigint(20) unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT,
	`device_id` VARCHAR(6) UNIQUE
);

drop table if exists `measures`;
CREATE TABLE `measures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `temperature` double(8,2) NOT NULL,
  `humidity` double(8,2) NOT NULL,
  `measure_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sequence_number` INT NOT NULL,
  `device` BIGINT(20) unsigned,
  FOREIGN KEY (`device`) REFERENCES `devices`(`id`),
  PRIMARY KEY (`id`)
);
