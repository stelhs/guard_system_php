-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 08, 2017 at 12:35 PM
-- Server version: 5.5.53-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `guard_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_logs`
--

CREATE TABLE IF NOT EXISTS `app_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `type` enum('urgent','error','warning','notice') NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='События в приложении' AUTO_INCREMENT=4 ;


-- --------------------------------------------------------

--
-- Table structure for table `blocking_sensors`
--

CREATE TABLE IF NOT EXISTS `blocking_sensors` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sense_id` bigint(20) NOT NULL,
  `mode` enum('lock','unlock') NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `guard_alarms`
--

CREATE TABLE IF NOT EXISTS `guard_alarms` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `action_id` bigint(20) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Сработки сигнализации' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `guard_states`
--

CREATE TABLE IF NOT EXISTS `guard_states` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `state` enum('sleep','ready') DEFAULT NULL,
  `method` enum('site','sms','remote') DEFAULT NULL,
  `ignore_sensors` varchar(256) NOT NULL COMMENT 'Список ID сенсоров через запятую, которые необходимо игнорировать',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=36 ;

-- --------------------------------------------------------

--
-- Table structure for table `incomming_sms`
--

CREATE TABLE IF NOT EXISTS `incomming_sms` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `phone` varchar(20) NOT NULL,
  `text` text NOT NULL,
  `undefined_text` tinyint(1) NOT NULL,
  `undefined_phone` tinyint(1) NOT NULL,
  `received_date` datetime NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=39 ;

-- --------------------------------------------------------

--
-- Table structure for table `io_input_actions`
--

CREATE TABLE IF NOT EXISTS `io_input_actions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `port` tinyint(4) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Input ports change value actions' AUTO_INCREMENT=147 ;

-- --------------------------------------------------------

--
-- Table structure for table `io_output_actions`
--

CREATE TABLE IF NOT EXISTS `io_output_actions` (
  `id` bigint(20) NOT NULL,
  `port` tinyint(4) NOT NULL,
  `state` tinyint(4) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `sensors`
--

CREATE TABLE IF NOT EXISTS `sensors` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `port` tinyint(4) NOT NULL,
  `normal_state` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `sensors`
--

INSERT INTO `sensors` (`id`, `name`, `port`, `normal_state`) VALUES
(1, 'Датчик объема 1', 2, 1),
(2, 'Датчик объёма 2', 3, 1),
(3, 'Датчик объёма 3', 4, 1),
(4, 'Датчик дверцы ВРУ', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sensor_actions`
--

CREATE TABLE IF NOT EXISTS `sensor_actions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sense_id` bigint(20) NOT NULL,
  `state` enum('normal','action') DEFAULT NULL,
  `guard_state` enum('sleep','ready') DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=46 ;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
