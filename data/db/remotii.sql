-- Adminer 3.6.3 MySQL dump

SET NAMES utf8;
SET foreign_key_checks = 0;
SET time_zone = 'SYSTEM';
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `acc_status`;
CREATE TABLE `acc_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `status_desc` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `acc_status` (`id`,`status_desc`) VALUES
(1,	'ACTIVE'),
(2,	'SUSPENDED'),
(3,	'DELINQUENT'),
(4,	'SUSPENDED_BY_ADMIN');

DROP TABLE IF EXISTS `end_user_payment_profile`;
CREATE TABLE `end_user_payment_profile` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `user_id` int(11) DEFAULT NULL,
  `authorizenet_profile_id` varchar(255) DEFAULT NULL,
  `payment_profile_id` varchar(255) DEFAULT NULL,
  `shipping_profile_id` varchar(255) DEFAULT NULL,
  `card_holder` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`profile_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `end_user_payment_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `end_user_payment_profile` (`profile_id`,`user_id`,`authorizenet_profile_id`,`payment_profile_id`,`shipping_profile_id`,`card_holder`) VALUES
(1,	4,	'cus_2gwAz87Qm6UPVh',	'',	'',	'Krishna Kumar');

DROP TABLE IF EXISTS `error`;
CREATE TABLE `error` (
  `error_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `mac_address` char(17) DEFAULT NULL,
  `message_id` bigint(20) DEFAULT NULL,
  `xmit_direction` char(1) NOT NULL,
  `error_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `error_kind` varchar(256) NOT NULL,
  `error_message` varchar(256) NOT NULL,
  PRIMARY KEY (`error_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `error` (`error_id`,`mac_address`,`message_id`,`xmit_direction`,`error_time`,`error_kind`,`error_message`) VALUES
(101,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 02:08:00',	'failure',	'inbound insert, token update failed.; frame: 120000000000b827ebc8a1151783e2bf0d01'),
(102,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 02:08:44',	'failure',	'inbound insert, token update failed.; frame: 120000000000b827ebc8a1151783e2bf0c01'),
(103,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 04:12:21',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(104,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 05:15:23',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(105,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 05:17:24',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(106,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 05:37:26',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(107,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 05:57:27',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(108,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 06:17:28',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(109,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 06:37:29',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(110,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 06:57:31',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(111,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 07:17:32',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(112,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 07:37:33',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(113,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 07:57:35',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(114,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 08:17:36',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(115,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 08:37:37',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(116,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 08:57:38',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(117,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 09:17:39',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(118,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 09:37:41',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(119,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 09:57:42',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(120,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 10:17:43',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(121,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 10:37:44',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(122,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 10:57:45',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(123,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 11:17:47',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(124,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 11:37:48',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(125,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 11:57:49',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(126,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 12:00:23',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(127,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 12:01:18',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(128,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 12:01:22',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(129,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 12:01:26',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(130,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 12:01:29',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(131,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 12:01:31',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(132,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 13:44:39',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(133,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 14:04:41',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(134,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 14:24:42',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(135,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 14:44:44',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(136,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 15:04:45',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(137,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 15:24:46',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(138,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 15:44:48',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(139,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 16:04:49',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(140,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 16:24:50',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(141,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 16:44:51',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(142,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 17:04:52',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(143,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 17:24:54',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(144,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 17:44:55',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(145,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 18:04:56',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(146,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 18:24:57',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(147,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 18:44:59',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(148,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 19:05:00',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(149,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 19:25:01',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(150,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 19:45:02',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(151,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 20:05:03',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(152,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 20:25:05',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(153,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 20:45:06',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(154,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 21:05:07',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(155,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 21:25:08',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(156,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 21:45:09',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(157,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 22:05:11',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(158,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 22:25:12',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(159,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 22:45:13',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(160,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:05:14',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(161,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:25:15',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(162,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:45:17',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(163,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:49:25',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(164,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:50:25',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(165,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:50:29',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(166,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:50:38',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(167,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:50:43',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(168,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:51:06',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(169,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:51:17',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(170,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:52:22',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(171,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:53:23',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(172,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:56:46',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0f02'),
(173,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-27 23:56:56',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(174,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 00:05:40',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(175,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 00:05:47',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(176,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 00:05:50',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(177,	'18-24-58-AE-47-11',	NULL,	'I',	'2013-09-28 00:07:13',	'failure',	'inbound insert, likely account unknown; frame: 120000000000182458ae47118eed0a20ee57'),
(178,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 00:15:52',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(179,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 00:35:53',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(180,	'21-E0-48-5F-08-38',	NULL,	'I',	'2013-09-28 00:53:59',	'failure',	'inbound insert, likely account unknown; frame: 12000000000021e0485f0838af90055e62ec'),
(181,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 00:55:54',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(182,	'43-2A-6F-74-CC-B4',	NULL,	'I',	'2013-09-28 00:56:30',	'failure',	'inbound insert, likely account unknown; frame: 120000000000432a6f74ccb469855122cc4d'),
(183,	'CB-18-3D-F2-A8-95',	NULL,	'I',	'2013-09-28 01:12:09',	'failure',	'inbound insert, likely account unknown; frame: 120000000000cb183df2a89565554a5f55da'),
(184,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 01:15:55',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(185,	'58-3B-51-F8-CB-96',	NULL,	'I',	'2013-09-28 01:28:45',	'failure',	'inbound insert, likely account unknown; frame: 120000000000583b51f8cb96dd8d7f5e19d7'),
(186,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 01:35:56',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(187,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'An existing connection was forcibly closed by the remote host'),
(188,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'An existing connection was forcibly closed by the remote host'),
(189,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'An existing connection was forcibly closed by the remote host'),
(190,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'An existing connection was forcibly closed by the remote host'),
(191,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'unknown'),
(192,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'unknown'),
(193,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'An existing connection was forcibly closed by the remote host'),
(194,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'unknown'),
(195,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'unknown'),
(196,	NULL,	NULL,	'U',	'2013-09-28 01:39:49',	'exception',	'unknown'),
(197,	'AD-F2-21-31-86-1A',	NULL,	'I',	'2013-09-28 01:41:15',	'failure',	'inbound insert, likely account unknown; frame: 120000000000adf22131861aaeb52578ec99'),
(198,	'70-00-56-53-8A-25',	NULL,	'I',	'2013-09-28 01:41:55',	'failure',	'inbound insert, likely account unknown; frame: 120000000000700056538a25765b0e0ba9c5'),
(199,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 01:55:58',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(200,	'EB-F6-04-06-43-BF',	NULL,	'I',	'2013-09-28 02:06:07',	'failure',	'inbound insert, likely account unknown; frame: 120000000000ebf6040643bf13b3791b35d6'),
(201,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 02:27:13',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(202,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 02:45:58',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(203,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 03:06:00',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(204,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 03:26:01',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(205,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 03:46:03',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(206,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 04:06:04',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(207,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 04:26:05',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(208,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 04:46:06',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(209,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 05:06:07',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(210,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 05:26:09',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(211,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 05:46:10',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(212,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 06:06:11',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(213,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 06:26:12',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(214,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 06:46:13',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(215,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 07:06:15',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(216,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 07:26:16',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(217,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 07:46:17',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(218,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 08:06:18',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(219,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 08:26:20',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(220,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 08:46:21',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(221,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 09:06:22',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(222,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 09:26:23',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(223,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 09:46:25',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(224,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 10:06:26',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(225,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 10:26:27',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(226,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 10:46:28',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(227,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 11:06:29',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(228,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 11:26:30',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(229,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 11:46:32',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(230,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 12:06:33',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(231,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 12:26:34',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(232,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 12:46:35',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(233,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 13:06:37',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(234,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 13:26:38',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(235,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 13:46:39',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(236,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 14:06:41',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(237,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 14:26:42',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(238,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 14:46:43',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(239,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 15:06:45',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(240,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 15:26:46',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(241,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 15:46:47',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(242,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 16:06:49',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(243,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 16:26:50',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(244,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 16:46:51',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(245,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 17:06:52',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(246,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 17:26:53',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(247,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 17:46:55',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(248,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 18:06:56',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(249,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 18:26:57',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(250,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 18:46:59',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(251,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 19:07:00',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(252,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 19:27:01',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(253,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 19:47:02',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(254,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 20:07:03',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(255,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 20:27:05',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(256,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 20:47:06',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(257,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 21:07:07',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(258,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 21:27:08',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(259,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 21:47:09',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(260,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 22:07:11',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(261,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 22:27:12',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(262,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 22:47:13',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(263,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 23:07:15',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(264,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 23:27:16',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(265,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-28 23:47:17',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(266,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 00:07:18',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(267,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 00:27:20',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(268,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 00:47:21',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(269,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 01:07:22',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(270,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 01:27:23',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(271,	'F8-BF-AB-BD-3F-C0',	NULL,	'I',	'2013-09-29 01:27:25',	'failure',	'inbound insert, likely account unknown; frame: 120000000000f8bfabbd3fc085c9d02f7628'),
(272,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 01:47:26',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(273,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 02:07:27',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(274,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-29 02:32:52',	'account validation',	'Incorrect token received.; frame: 120000000000b827ebc8a11548245eae0d02'),
(275,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-29 22:36:29',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0302'),
(276,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-29 22:37:17',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(277,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-29 22:54:51',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(278,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-29 23:14:53',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(279,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-29 23:34:54',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(280,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-29 23:54:55',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(281,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 00:14:59',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(282,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 00:35:00',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(283,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 00:43:58',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(284,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 00:55:02',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(285,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 01:04:36',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(286,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 01:15:02',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(287,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 01:24:36',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(288,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 01:35:04',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(289,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 01:44:37',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(290,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 01:55:04',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(291,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 02:04:38',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(292,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 02:15:06',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(293,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 02:24:40',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(294,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 02:35:09',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(295,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 02:44:42',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(296,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 02:55:07',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(297,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 03:04:43',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(298,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 03:15:10',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(299,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 03:24:44',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(300,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 03:35:12',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(301,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 03:44:45',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(302,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 03:55:14',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(303,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 04:04:46',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(304,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 04:15:15',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(305,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 04:24:47',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(306,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 04:35:16',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(307,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 04:44:49',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(308,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 04:55:17',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(309,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 05:04:50',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(310,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 05:15:17',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(311,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 05:24:52',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(312,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 05:35:20',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(313,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 05:44:52',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(314,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 05:55:22',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(315,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 06:04:54',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(316,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 06:15:23',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(317,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 06:24:55',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(318,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 06:35:24',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(319,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 06:44:56',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(320,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 06:55:24',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(321,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 07:04:57',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(322,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 07:15:26',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(323,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 07:24:58',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(324,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 07:35:26',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(325,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 07:45:00',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(326,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 07:55:29',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(327,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 08:05:01',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(328,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 08:15:31',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(329,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 08:25:02',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(330,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 08:35:33',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(331,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-09-30 08:45:04',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0d02'),
(332,	'B8-27-EB-09-9B-B0',	NULL,	'I',	'2013-09-30 08:55:34',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827eb099bb048245eae0702'),
(333,	NULL,	NULL,	'O',	'2013-10-01 07:41:50',	'failure',	'Communications link failure\n\nThe last packet successfully received from the server was 3,411 milliseconds ago.  The last packet sent successfully to the server was 416 milliseconds ago.'),
(334,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-10-03 10:33:01',	'failure',	'inbound insert, likely account unknown; frame: 120000000000b827ebc8a11548245eae0c00'),
(335,	NULL,	NULL,	'O',	'2013-10-03 11:06:55',	'failure',	'Communications link failure\n\nThe last packet successfully received from the server was 2,996 milliseconds ago.  The last packet sent successfully to the server was 0 milliseconds ago.'),
(336,	NULL,	NULL,	'O',	'2013-10-03 11:30:59',	'failure',	'Communications link failure\n\nThe last packet successfully received from the server was 2,996 milliseconds ago.  The last packet sent successfully to the server was 0 milliseconds ago.'),
(337,	NULL,	NULL,	'O',	'2013-10-03 11:31:15',	'failure',	'Communications link failure\n\nThe last packet successfully received from the server was 2,536 milliseconds ago.  The last packet sent successfully to the server was 0 milliseconds ago.'),
(338,	NULL,	NULL,	'U',	'2013-10-03 14:32:15',	'exception',	'Connection timed out'),
(339,	'B8-27-EB-C8-A1-15',	NULL,	'I',	'2013-10-04 06:18:38',	'account validation',	'account unknown; frame: 120000000000b827ebc8a11548245eae0807');

DROP TABLE IF EXISTS `inbound`;
CREATE TABLE `inbound` (
  `message_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `mac_address` char(17) NOT NULL,
  `remote_address` varchar(256) NOT NULL,
  `remote_port` int(11) NOT NULL,
  `receive_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `din` tinyint(4) unsigned NOT NULL,
  `dout` tinyint(4) unsigned NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `fk_inbound_acct` (`mac_address`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `inbound` (`message_id`,`mac_address`,`remote_address`,`remote_port`,`receive_time`,`din`,`dout`) VALUES
(1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:14:17',	14,	7),
(2,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 12:16:10',	3,	0),
(3,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:24:17',	14,	7),
(4,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 12:26:10',	3,	0),
(5,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:34:17',	14,	7),
(6,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 12:36:10',	3,	0),
(7,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:44:17',	14,	7),
(8,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 12:46:10',	3,	0),
(9,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:51:50',	14,	5),
(10,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:51:51',	8,	5),
(11,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:51:56',	10,	7),
(12,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:51:56',	10,	7),
(13,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:01',	10,	7),
(14,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:01',	15,	7),
(15,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:08',	15,	7),
(16,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:08',	14,	7),
(17,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:13',	14,	7),
(18,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:13',	15,	7),
(19,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:19',	15,	7),
(20,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:19',	14,	7),
(21,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:23',	14,	7),
(22,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:23',	15,	7),
(23,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:54:17',	15,	7),
(24,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 12:56:10',	3,	0),
(25,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 13:04:17',	15,	7),
(26,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 13:06:10',	3,	0),
(27,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 13:14:17',	15,	7),
(28,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 13:16:10',	3,	0),
(29,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 13:24:17',	15,	7),
(30,	'B8-27-EB-09-9B-B0',	'50.157.220.13',	41971,	'2013-10-04 13:26:10',	3,	0),
(31,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 13:34:17',	15,	7);

DELIMITER ;;

CREATE TRIGGER `change_remotii_last_input_status` AFTER INSERT ON `inbound` FOR EACH ROW
BEGIN
      SELECT remotii_last_input_status into @status FROM remotii WHERE mac_address = NEW.mac_address;
      IF @status != NEW.din  THEN 
        UPDATE remotii SET remotii_last_input_status = NEW.din WHERE mac_address = NEW.mac_address;

        SELECT @status ^ NEW.din into @valuexor;

        IF (@valuexor & 1) = 1 THEN
            SELECT ( NEW.din & 1 )  into @custom1;
          
            SELECT notification_trigger, notification_email into @notificationstatus, @email FROM remotii r INNER JOIN user_remotii ur 
            ON (r.remotii_id = ur.remotii_id)
            INNER JOIN  user_remotii_input_config urc 
            ON (urc.user_remotii_id = ur.user_remotii_id) 
            WHERE r.mac_address = NEW.mac_address AND urc.pin_number = 1;

            IF( (@custom1 = 1 and @notificationstatus = 1)  or  (@custom1 = 0 and @notificationstatus = 0)) THEN
                 INSERT INTO notification_email SET mac_address = NEW.mac_address, email = @email, state = NEW.din;
            END IF;
        END IF;

        IF (@valuexor & 2) = 2 THEN
            SELECT ( NEW.din & 2 )  into @custom1;
          
            SELECT notification_trigger, notification_email into @notificationstatus, @email FROM remotii r INNER JOIN user_remotii ur 
            ON (r.remotii_id = ur.remotii_id)
            INNER JOIN  user_remotii_input_config urc 
            ON (urc.user_remotii_id = ur.user_remotii_id) 
            WHERE r.mac_address = NEW.mac_address AND urc.pin_number = 2;

            IF( (@custom1 = 2 and @notificationstatus = 1)  or  (@custom1 = 0 and @notificationstatus = 0)) THEN
                 INSERT INTO notification_email SET mac_address = NEW.mac_address, email = @email, state = NEW.din;
            END IF;
        END IF;

         IF (@valuexor & 4) = 4 THEN
            SELECT ( NEW.din & 4 )  into @custom1;
          
            SELECT notification_trigger, notification_email into @notificationstatus, @email FROM remotii r INNER JOIN user_remotii ur 
            ON (r.remotii_id = ur.remotii_id)
            INNER JOIN  user_remotii_input_config urc 
            ON (urc.user_remotii_id = ur.user_remotii_id) 
            WHERE r.mac_address = NEW.mac_address AND urc.pin_number = 3;

            IF( (@custom1 = 4 and @notificationstatus = 1)  or  (@custom1 = 0 and @notificationstatus = 0)) THEN
                 INSERT INTO notification_email SET mac_address = NEW.mac_address, email = @email, state = NEW.din;
            END IF;
        END IF;

         IF (@valuexor & 8) = 8 THEN
            SELECT ( NEW.din & 8 )  into @custom1;
          
            SELECT notification_trigger, notification_email into @notificationstatus, @email FROM remotii r INNER JOIN user_remotii ur 
            ON (r.remotii_id = ur.remotii_id)
            INNER JOIN  user_remotii_input_config urc 
            ON (urc.user_remotii_id = ur.user_remotii_id) 
            WHERE r.mac_address = NEW.mac_address AND urc.pin_number = 4;

            IF( (@custom1 = 8 and @notificationstatus = 1)  or  (@custom1 = 0 and @notificationstatus = 0)) THEN
                 INSERT INTO notification_email SET mac_address = NEW.mac_address, email = @email, state = NEW.din;
            END IF;
        END IF;

     END IF;
END;;

DELIMITER ;

DROP TABLE IF EXISTS `notification_email`;
CREATE TABLE `notification_email` (
  `nid` bigint(20) NOT NULL AUTO_INCREMENT,
  `mac_address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `flag` smallint(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `notification_email` (`nid`,`mac_address`,`email`,`state`,`flag`) VALUES
(1,	'B8-27-EB-C8-A1-15',	NULL,	13,	1),
(2,	'B8-27-EB-C8-A1-15',	'',	13,	1),
(3,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(4,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(5,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(6,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(7,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(8,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(9,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(10,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(11,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(12,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(13,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(14,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(15,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(16,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(17,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(18,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(19,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(20,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(21,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(22,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(23,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(24,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(25,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(26,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(27,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(28,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(29,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(30,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(31,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(32,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(33,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(34,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(35,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(36,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(37,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(38,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(39,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(40,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(41,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(42,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(43,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(44,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(45,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(46,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(47,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(48,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(49,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(50,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(51,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(52,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(53,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(54,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(55,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(56,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(57,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(58,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(59,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(60,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(61,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(62,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(63,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(64,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(65,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(66,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(67,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(68,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(69,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(70,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(71,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(72,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(73,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	1),
(74,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	8,	1),
(75,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	8,	1),
(76,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	8,	1),
(77,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(78,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(79,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(80,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(81,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(82,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(83,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(84,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(85,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(86,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(87,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(88,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(89,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(90,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(91,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(92,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(93,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(94,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(95,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(96,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(97,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(98,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(99,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(100,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(101,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(102,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(103,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(104,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(105,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(106,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(107,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(108,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(109,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(110,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(111,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(112,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(113,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(114,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(115,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(116,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(117,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(118,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(119,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(120,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(121,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(122,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(123,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(124,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(125,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(126,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(127,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(128,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(129,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(130,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(131,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(132,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(133,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(134,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(135,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(136,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(137,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(138,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(139,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(140,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(141,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(142,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(143,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(144,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(145,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(146,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(147,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(148,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(149,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	1),
(150,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(151,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(152,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(153,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(154,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(155,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(156,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(157,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(158,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(159,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(160,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(161,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(162,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(163,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(164,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(165,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(166,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(167,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(168,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(169,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(170,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(171,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(172,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(173,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(174,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(175,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(176,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(177,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(178,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(179,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(180,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(181,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(182,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(183,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(184,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(185,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(186,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(187,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(188,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(189,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(190,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(191,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(192,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(193,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(194,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(195,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(196,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(197,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(198,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	8,	0),
(199,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	8,	0),
(200,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	12,	0),
(201,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	13,	0),
(212,	'B8-27-EB-C8-A1-15',	'krishna@finoit.com',	14,	0),
(216,	'B8-27-EB-C8-A1-15',	'',	8,	0),
(217,	'B8-27-EB-C8-A1-15',	'',	8,	0),
(218,	'B8-27-EB-C8-A1-15',	'',	14,	0),
(219,	'B8-27-EB-C8-A1-15',	'',	14,	0),
(220,	'B8-27-EB-09-9B-B0',	'',	3,	0),
(221,	'B8-27-EB-09-9B-B0',	'',	3,	0),
(222,	'B8-27-EB-09-9B-B0',	'',	3,	0),
(223,	'B8-27-EB-09-9B-B0',	'',	3,	0),
(224,	'B8-27-EB-09-9B-B0',	'',	3,	0),
(225,	'B8-27-EB-09-9B-B0',	'',	3,	0),
(226,	'B8-27-EB-09-9B-B0',	'',	3,	0),
(227,	'B8-27-EB-09-9B-B0',	'',	3,	0);

DROP TABLE IF EXISTS `outbound`;
CREATE TABLE `outbound` (
  `message_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `remotii_id` int(11) NOT NULL,
  `mac_address` char(17) NOT NULL,
  `remote_address` varchar(256) DEFAULT NULL,
  `remote_port` int(11) DEFAULT NULL,
  `insert_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `xmit_time` timestamp NULL DEFAULT NULL,
  `dout_set` tinyint(4) NOT NULL,
  `dout_clr` tinyint(4) NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `fk_outbound_acct` (`mac_address`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `outbound` (`message_id`,`remotii_id`,`mac_address`,`remote_address`,`remote_port`,`insert_time`,`xmit_time`,`dout_set`,`dout_clr`) VALUES
(10317,	1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:51:50',	'2013-10-04 12:51:20',	0,	2),
(10318,	1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:51:55',	'2013-10-04 12:51:25',	2,	0),
(10319,	1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:51:59',	'2013-10-04 12:51:30',	1,	1),
(10320,	1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:07',	'2013-10-04 12:51:37',	1,	1),
(10321,	1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:11',	'2013-10-04 12:51:42',	1,	1),
(10322,	1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:18',	'2013-10-04 12:51:48',	1,	1),
(10323,	1,	'B8-27-EB-C8-A1-15',	'50.150.44.131',	44277,	'2013-10-04 12:52:21',	'2013-10-04 12:51:52',	1,	1);

DROP TABLE IF EXISTS `payment_stats`;
CREATE TABLE `payment_stats` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `payment_cycle` int(11) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_source` varchar(255) DEFAULT NULL,
  `payment_status` smallint(5) DEFAULT NULL,
  `executed_on` int(11) DEFAULT NULL,
  `payment_response` varchar(255) DEFAULT NULL COMMENT 'Response from payment gateway',
  `payment_flag` varchar(255) NOT NULL,
  `trans_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `remotii`;
CREATE TABLE `remotii` (
  `remotii_id` int(11) NOT NULL AUTO_INCREMENT,
  `mac_address` varchar(255) NOT NULL,
  `service_provider_id` bigint(20) DEFAULT NULL,
  `remotii_status` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  `token` int(11) NOT NULL,
  `remotii_last_input_status` int(11) NOT NULL,
  PRIMARY KEY (`remotii_id`),
  UNIQUE KEY `mac_address` (`mac_address`),
  KEY `service_provider_id` (`service_provider_id`),
  CONSTRAINT `remotii_ibfk_1` FOREIGN KEY (`service_provider_id`) REFERENCES `service_provider` (`service_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `remotii` (`remotii_id`,`mac_address`,`service_provider_id`,`remotii_status`,`updated_by`,`updated_on`,`token`,`remotii_last_input_status`) VALUES
(1,	'B8-27-EB-C8-A1-15',	2511661625,	1,	2,	1380890788,	-1369562040,	15);

DROP TABLE IF EXISTS `service_provider`;
CREATE TABLE `service_provider` (
  `service_provider_id` bigint(20) NOT NULL COMMENT 'PK',
  `contact_fname` varchar(40) DEFAULT NULL,
  `contact_lname` varchar(40) DEFAULT NULL,
  `contact_phone` varchar(40) DEFAULT NULL,
  `contact_email` varchar(50) DEFAULT NULL,
  `acc_status` varchar(255) DEFAULT NULL,
  `card_number` varchar(255) DEFAULT NULL,
  `card_holder` varchar(255) DEFAULT NULL,
  `contracted_price` decimal(10,2) DEFAULT NULL,
  `end_user_price` decimal(10,2) DEFAULT NULL,
  `allow_end_user_billing` smallint(5) DEFAULT NULL,
  `service_fee` decimal(10,2) DEFAULT NULL,
  `last_payment_status` smallint(5) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  `company_name` varchar(255) DEFAULT NULL,
  `authorizenet_profile_id` varchar(255) DEFAULT NULL,
  `payment_profile_id` varchar(255) DEFAULT NULL,
  `shipping_profile_id` varchar(255) DEFAULT NULL,
  `last_payment_stat_id` int(11) DEFAULT NULL,
  `acc_created_on` int(11) DEFAULT NULL,
  PRIMARY KEY (`service_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `service_provider` (`service_provider_id`,`contact_fname`,`contact_lname`,`contact_phone`,`contact_email`,`acc_status`,`card_number`,`card_holder`,`contracted_price`,`end_user_price`,`allow_end_user_billing`,`service_fee`,`last_payment_status`,`updated_by`,`updated_on`,`company_name`,`authorizenet_profile_id`,`payment_profile_id`,`shipping_profile_id`,`last_payment_stat_id`,`acc_created_on`) VALUES
(2511661625,	'service',	'provider',	'1234567890',	'rsp@gmail.com',	'1',	'xxxxxxxxxxxx',	'',	20.00,	25.00,	0,	0.00,	NULL,	1,	1380890027,	'RSP',	NULL,	NULL,	NULL,	NULL,	1380890027);

DROP TABLE IF EXISTS `service_provider_admins`;
CREATE TABLE `service_provider_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_provider_id` bigint(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_provider_id` (`service_provider_id`),
  CONSTRAINT `service_provider_admins_ibfk_1` FOREIGN KEY (`service_provider_id`) REFERENCES `service_provider` (`service_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `service_provider_admins` (`id`,`service_provider_id`,`user_id`) VALUES
(1,	2511661625,	2);

DROP TABLE IF EXISTS `service_provider_input_config`;
CREATE TABLE `service_provider_input_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `service_provider_id` bigint(20) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `active_label_text` varchar(255) DEFAULT NULL,
  `active_label_color` varchar(255) DEFAULT NULL,
  `inactive_label_text` varchar(255) DEFAULT NULL,
  `inactive_label_color` varchar(255) DEFAULT NULL,
  `is_enabled` smallint(5) DEFAULT NULL,
  `enable_notification` smallint(5) DEFAULT NULL COMMENT 'enable/disable',
  `notification_trigger` smallint(5) DEFAULT NULL COMMENT 'active/inactive',
  `notification_email` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  PRIMARY KEY (`config_id`),
  KEY `service_provider_id` (`service_provider_id`),
  CONSTRAINT `service_provider_input_config_ibfk_1` FOREIGN KEY (`service_provider_id`) REFERENCES `service_provider` (`service_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `service_provider_input_config` (`config_id`,`service_provider_id`,`name`,`active_label_text`,`active_label_color`,`inactive_label_text`,`inactive_label_color`,`is_enabled`,`enable_notification`,`notification_trigger`,`notification_email`,`updated_by`,`updated_on`) VALUES
(1,	2511661625,	'Low Temperature',	'Cold',	'#7030a0',	'Not Cold',	'#c00000',	1,	0,	0,	NULL,	2147483647,	1380890426),
(2,	2511661625,	'Ringing',	'Ringing',	'#ff0000',	'Not Ringing',	'#c00000',	1,	0,	0,	NULL,	2147483647,	1380890426),
(3,	2511661625,	'Ready',	'Ready',	'#00b050',	'Not Ready',	'#c00000',	1,	0,	0,	NULL,	2147483647,	1380890426),
(4,	2511661625,	'Armed',	'Armed',	'#0070c0',	'Not Armed',	'#c00000',	1,	0,	0,	NULL,	2147483647,	1380890426);

DROP TABLE IF EXISTS `service_provider_output_config`;
CREATE TABLE `service_provider_output_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `service_provider_id` bigint(20) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `active_label_text` varchar(255) DEFAULT NULL,
  `active_label_color` varchar(255) DEFAULT NULL,
  `inactive_label_text` varchar(255) DEFAULT NULL,
  `inactive_label_color` varchar(255) DEFAULT NULL,
  `is_enabled` smallint(5) DEFAULT NULL,
  `is_output_momentary` smallint(5) DEFAULT NULL,
  `output_initial_state` smallint(5) DEFAULT NULL COMMENT 'active/inactive',
  `enable_notification` smallint(5) DEFAULT NULL COMMENT 'enable/disable',
  `notification_trigger` smallint(5) DEFAULT NULL COMMENT 'active/inactive',
  `notification_email` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  PRIMARY KEY (`config_id`),
  KEY `service_provider_id` (`service_provider_id`),
  CONSTRAINT `service_provider_output_config_ibfk_1` FOREIGN KEY (`service_provider_id`) REFERENCES `service_provider` (`service_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `service_provider_output_config` (`config_id`,`service_provider_id`,`name`,`active_label_text`,`active_label_color`,`inactive_label_text`,`inactive_label_color`,`is_enabled`,`is_output_momentary`,`output_initial_state`,`enable_notification`,`notification_trigger`,`notification_email`,`updated_by`,`updated_on`) VALUES
(1,	2511661625,	'Key Switch',	'Not Turned',	'#e36c09',	'Turned',	'#e36c09',	1,	1,	1,	0,	0,	NULL,	2147483647,	1380890426),
(2,	2511661625,	'Zone 1',	'Closed',	'#c00000',	'Open',	'#00b050',	1,	0,	1,	0,	0,	NULL,	2147483647,	1380890426),
(3,	2511661625,	'Zone 2',	'Closed',	'#c00000',	'Open',	'#00b050',	1,	0,	1,	0,	0,	NULL,	2147483647,	1380890426);

DROP TABLE IF EXISTS `service_provider_receiving_payment_details`;
CREATE TABLE `service_provider_receiving_payment_details` (
  `receiving_payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `service_provider_id` bigint(20) NOT NULL,
  `routing_number` bigint(20) NOT NULL,
  `account_type` varchar(255) NOT NULL,
  `account_number` bigint(20) NOT NULL,
  `name_on_bank` varchar(255) NOT NULL,
  `stripe_acc_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`receiving_payment_id`),
  KEY `service_provider_id` (`service_provider_id`),
  CONSTRAINT `service_provider_receiving_payment_details_ibfk_1` FOREIGN KEY (`service_provider_id`) REFERENCES `service_provider` (`service_provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `username` varchar(255) NOT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `user_role_id` smallint(5) DEFAULT NULL COMMENT '1-admin, 2-service_manager, 3-client',
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `acc_status` smallint(5) DEFAULT '1',
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  `last_login` int(11) DEFAULT NULL COMMENT 'timestamp',
  `display_name` varchar(255) DEFAULT NULL,
  `created_on` int(11) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `user` (`user_id`,`username`,`fname`,`lname`,`phone`,`email`,`password`,`user_role_id`,`street`,`city`,`state`,`country`,`zip_code`,`acc_status`,`updated_by`,`updated_on`,`last_login`,`display_name`,`created_on`) VALUES
(1,	'admin',	'Admin',	NULL,	NULL,	NULL,	'$2y$14$Rh5zD7DIqV/P2WHScl5GEOoZliuW9h.gxInTFNJrt3uKbNIhIOoAW',	1,	NULL,	NULL,	NULL,	NULL,	NULL,	1,	NULL,	NULL,	NULL,	NULL,	NULL),
(2,	'rsp',	'service',	'provider',	'1234567890',	'test@gmail.com',	'$2y$14$lUzXObuO.AmglUY1ZXFkK.JG5WiYj7Zeqdzk/Q.rWsyl62VQWc9uW',	2,	NULL,	NULL,	NULL,	NULL,	NULL,	1,	1,	1380890081,	NULL,	NULL,	1380890081),
(3,	'eric',	'Eric',	'Shufro',	'8324745930',	'eric@shufro.com',	'$2y$14$lUzXObuO.AmglUY1ZXFkK.JG5WiYj7Zeqdzk/Q.rWsyl62VQWc9uW',	3,	'108 Cabin Gate',	'Peachtree City',	'Georgia',	NULL,	'30269',	1,	NULL,	NULL,	NULL,	NULL,	1380890915),
(4,	'Ktest',	'Ktest',	'Ktest',	'9818936870',	'Ktest@yopmail.com',	'$2y$14$RXsXIC0n7ECDVg2YAMuF4u72Nb4C22.jSE3SdHXBh2VwNYXBMahEW',	3,	'sec 64',	'noida',	'up',	NULL,	'201301',	1,	NULL,	NULL,	NULL,	NULL,	1380892000);

DROP TABLE IF EXISTS `user_password_reset`;
CREATE TABLE `user_password_reset` (
  `request_key` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_time` datetime NOT NULL,
  PRIMARY KEY (`request_key`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `user_remotii`;
CREATE TABLE `user_remotii` (
  `user_remotii_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `user_id` int(11) DEFAULT NULL,
  `remotii_id` int(11) NOT NULL,
  `remotii_name` varchar(255) DEFAULT NULL,
  `is_default` smallint(5) DEFAULT '0',
  `is_default_cofig` smallint(5) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  PRIMARY KEY (`user_remotii_id`),
  KEY `remotii_id` (`remotii_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_remotii_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  CONSTRAINT `user_remotii_ibfk_1` FOREIGN KEY (`remotii_id`) REFERENCES `remotii` (`remotii_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `user_remotii` (`user_remotii_id`,`user_id`,`remotii_id`,`remotii_name`,`is_default`,`is_default_cofig`,`updated_by`,`updated_on`) VALUES
(1,	3,	1,	'Home',	0,	0,	3,	1380891105);

DROP TABLE IF EXISTS `user_remotii_input_config`;
CREATE TABLE `user_remotii_input_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `user_remotii_id` int(11) DEFAULT NULL,
  `pin_number` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `active_label_text` varchar(255) DEFAULT NULL,
  `active_label_color` varchar(255) DEFAULT NULL,
  `inactive_label_text` varchar(255) DEFAULT NULL,
  `inactive_label_color` varchar(255) DEFAULT NULL,
  `is_enabled` smallint(5) DEFAULT NULL,
  `enable_notification` smallint(5) DEFAULT NULL COMMENT 'enable/disable',
  `notification_trigger` smallint(5) DEFAULT NULL COMMENT 'active/inactive',
  `notification_email` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  PRIMARY KEY (`config_id`),
  KEY `user_remotii_id` (`user_remotii_id`),
  CONSTRAINT `user_remotii_input_config_ibfk_1` FOREIGN KEY (`user_remotii_id`) REFERENCES `user_remotii` (`user_remotii_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `user_remotii_input_config` (`config_id`,`user_remotii_id`,`pin_number`,`name`,`active_label_text`,`active_label_color`,`inactive_label_text`,`inactive_label_color`,`is_enabled`,`enable_notification`,`notification_trigger`,`notification_email`,`updated_by`,`updated_on`) VALUES
(1,	1,	1,	'Low Temperature',	'Cold',	'#7030a0',	'Not Cold',	'#c00000',	1,	0,	0,	'',	3,	1380891105),
(2,	1,	2,	'Ringing',	'Ringing',	'#ff0000',	'Not Ringing',	'#c00000',	1,	0,	0,	'',	3,	1380891105),
(3,	1,	3,	'Ready',	'Ready',	'#00b050',	'Not Ready',	'#c00000',	1,	0,	0,	'',	3,	1380891105),
(4,	1,	4,	'Armed',	'Armed',	'#0070c0',	'Not Armed',	'#c00000',	1,	0,	0,	'',	3,	1380891105);

DROP TABLE IF EXISTS `user_remotii_output_config`;
CREATE TABLE `user_remotii_output_config` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `user_remotii_id` int(11) DEFAULT NULL,
  `pin_number` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `active_label_text` varchar(255) DEFAULT NULL,
  `active_label_color` varchar(255) DEFAULT NULL,
  `inactive_label_text` varchar(255) DEFAULT NULL,
  `inactive_label_color` varchar(255) DEFAULT NULL,
  `is_enabled` smallint(5) DEFAULT NULL,
  `is_output_momentary` smallint(5) DEFAULT NULL,
  `output_initial_state` smallint(5) DEFAULT NULL COMMENT 'active/inactive',
  `enable_notification` smallint(5) DEFAULT NULL COMMENT 'enable/disable',
  `notification_trigger` smallint(5) DEFAULT NULL COMMENT 'active/inactive',
  `notification_email` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` int(11) DEFAULT NULL COMMENT 'timestamp',
  PRIMARY KEY (`config_id`),
  KEY `user_remotii_id` (`user_remotii_id`),
  CONSTRAINT `user_remotii_output_config_ibfk_1` FOREIGN KEY (`user_remotii_id`) REFERENCES `user_remotii` (`user_remotii_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `user_remotii_output_config` (`config_id`,`user_remotii_id`,`pin_number`,`name`,`active_label_text`,`active_label_color`,`inactive_label_text`,`inactive_label_color`,`is_enabled`,`is_output_momentary`,`output_initial_state`,`enable_notification`,`notification_trigger`,`notification_email`,`updated_by`,`updated_on`) VALUES
(1,	1,	1,	'Key Switch',	'Not Turned',	'#e36c09',	'Turned',	'#e36c09',	1,	1,	1,	0,	0,	'',	3,	1380891105),
(2,	1,	2,	'Zone 1',	'Closed',	'#c00000',	'Open',	'#00b050',	1,	0,	1,	0,	0,	'',	3,	1380891105),
(3,	1,	3,	'Zone 2',	'Closed',	'#c00000',	'Open',	'#00b050',	1,	0,	1,	0,	0,	'',	3,	1380891105);

-- 2013-10-04 13:35:09
