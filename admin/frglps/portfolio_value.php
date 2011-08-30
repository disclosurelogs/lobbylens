#!/usr/bin/env php
<?php

	$db = new mysqli("localhost", "team7", "", "team7");
	/* check connection */
	if (mysqli_connect_errno()) {
	    	printf("Connect failed: %s\n", mysqli_connect_error());
    		exit();
	}

	// give a portfolio id
	// list the total value allocated to the portfolio
	// and the politicians allocated to that portfolio

	class PortfolioData {

		protected $db;

		protected $id;

		/**
		* @param db connection
		* @param $id portfolio id
		*/
		public function __construct($db, $id) {
			$this->db = $db;
			$this->id = $id;
		}

		/**
		* @return float
		*/
		public function findTotalValue() {
	
			$sql = sprintf("SELECT sum(c.value) as value FROM contractnotice as c WHERE c.portfolio=%d", $this->id);
			if(!$result = $this->db->query($sql)) {
				printf("Error: %s\n", $this->db->error);
			}

			$row = $result->fetch_assoc();
			return $row["value"];
		}

		protected function loadPortfolio() {
		}

	}

	$p = new PortfolioData($db, 4);
	printf("Value for %s\n\n", $p->findTotalValue());
	
	$db->close();	
