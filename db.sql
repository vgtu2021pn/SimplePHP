SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `company`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `deptno` int NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `location` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`deptno`, `name`, `location`) VALUES
(14082, 'Finance', 'New York'),
(3376894, 'AS', NULL),
(3889860, 'Development', 'San Jose'),
(8255163, 'AS', NULL);

--
-- Triggers `departments`
--
DELIMITER $$
CREATE TRIGGER `RandomDeptNo` BEFORE INSERT ON `departments` FOR EACH ROW BEGIN 

REPEAT
SET @num = FLOOR(RAND()*10000000);
UNTIL NEW.deptno NOT IN (SELECT deptno FROM departments WHERE deptno = @num)
END REPEAT;

IF NEW.deptno IS NULL THEN 
SET NEW.deptno = @num; 
END IF; 

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `empno` bigint NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `job` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manager` int DEFAULT NULL,
  `hiredate` date DEFAULT NULL,
  `salary` double(7,2) DEFAULT NULL,
  `commission` double(7,2) DEFAULT NULL,
  `deptno` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`empno`, `name`, `job`, `manager`, `hiredate`, `salary`, `commission`, `deptno`) VALUES
(196102, 'Mara Martin', 'Analyst', NULL, NULL, 6000.00, NULL, 14082),
(868314, 'Sam Smith', 'Programmer', NULL, NULL, 5000.00, NULL, 3889860),
(3800835, 'Yun Yates', 'Analyst', NULL, NULL, 5500.00, NULL, 3889860);

--
-- Triggers `employees`
--
DELIMITER $$
CREATE TRIGGER `RandomEmpNo` BEFORE INSERT ON `employees` FOR EACH ROW BEGIN 
REPEAT
SET @num = FLOOR(RAND()*10000000);
UNTIL NEW.empno NOT IN (SELECT empno FROM employees WHERE empno = @num) END REPEAT;
IF NEW.empno IS NULL THEN 
SET NEW.empno = @num; 
END IF; 
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lza_log`
--

CREATE TABLE `lza_log` (
  `log_time` datetime NOT NULL,
  `no` mediumint UNSIGNED NOT NULL,
  `message` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `file` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `line` mediumint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci COMMENT='System Logs';

-- --------------------------------------------------------

--
-- Table structure for table `lza_sessions`
--

CREATE TABLE `lza_sessions` (
  `id` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `access` datetime NOT NULL,
  `data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci COMMENT='Sessions of the Users';

-- --------------------------------------------------------

--
-- Table structure for table `lza_users`
--

CREATE TABLE `lza_users` (
  `id` int UNSIGNED NOT NULL,
  `username` char(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `password` char(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `nickname` varchar(125) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `status` tinyint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci COMMENT='User Accounts Page';

--
-- Dumping data for table `lza_users`
--

INSERT INTO `lza_users` (`id`, `username`, `password`, `nickname`, `status`) VALUES
(1, 'admin', '5f4dcc3b5aa765d61d8327deb882cf99', 'admin', 1);

-- --------------------------------------------------------

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`deptno`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`empno`),
  ADD KEY `employee_dept_no_fk_idx` (`deptno`);

--
-- Indexes for table `lza_sessions`
--
ALTER TABLE `lza_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lza_users`
--
ALTER TABLE `lza_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for table `lza_users`
--
ALTER TABLE `lza_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_deptno` FOREIGN KEY (`deptno`) REFERENCES `departments` (`deptno`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
