-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 08, 2014 at 06:53 AM
-- Server version: 5.1.36
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `database`
--

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE IF NOT EXISTS `small_agents` (
  `AGENT_ID` INT(11) NOT NULL AUTO_INCREMENT,
  `AGENT_NAME` varchar(40) DEFAULT NULL,
  `WORKING_AREA` varchar(35) DEFAULT NULL,
  `COMMISSION` decimal(10,2) DEFAULT NULL,
  `PHONE_NO` varchar(15) DEFAULT NULL,
  `COUNTRY` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`AGENT_ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `agents`
--

INSERT INTO `small_agents` (`AGENT_NAME`, `WORKING_AREA`, `COMMISSION`, `PHONE_NO`, `COUNTRY`) VALUES
('Ramasundar', 'Bangalore', '0.15', '077-25814763', ''),
('Alex', 'London', '0.13', '075-12458969', ''),
('Alford', 'New York', '0.12', '044-25874365', ''),
('Ravi Kumar', 'Bangalore', '0.15', '077-45625874', ''),
('Santakumar', 'Chennai', '0.14', '007-22388644', ''),
('Lucida', 'San Jose', '0.12', '044-52981425', ''),
('Anderson', 'Brisban', '0.13', '045-21447739', ''),
('Subbarao', 'Bangalore', '0.14', '077-12346674', ''),
('Mukesh', 'Mumbai', '0.11', '029-12358964', ''),
('McDen', 'London', '0.15', '078-22255588', ''),
('Ivan', 'Torento', '0.15', '008-22544166', ''),
('Benjamin', 'Hampshair', '0.11', '008-22536178', '')

-- --------------------------------------------------------
