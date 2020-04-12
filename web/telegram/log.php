<?php

/*
 * Copyright 2020 Mirco Soderi
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the "Software"), 
 * to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the 
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
 * DEALINGS IN THE SOFTWARE.
 *
 */
 
 /*******************************************************************************
* FPDF                                                                         *
*                                                                              *
* Version: 1.82                                                                *
* Date:    2019-12-07                                                          *
* Author:  Olivier PLATHEY                                                     *
*******************************************************************************/

session_start();
require('../lib/fpdf182/fpdf.php');
require_once("../lib/const.php");
class PDF extends FPDF
{
	function LoadData($file)
	{
		$lines = file($file);
		$data = array();
		foreach($lines as $line)
			$data[] = explode(';',trim($line));
		return $data;
	}
	function ImprovedTable($header, $data)
	{
		$w = array(40, 20, 200, 20);
		for($i=0;$i<count($header);$i++)
			$this->Cell($w[$i],7,$header[$i],1,0,'C');
		$this->Ln();
		$fill = true;
		$this->SetFillColor(200,200,200);		
		$c = 0;
		foreach($data as $row)
		{
			if($c == 0) $t = "T"; else $t = "";
			if($c == 27) { 
				$b = "B"; 			
			} else $b = "";			
			if($c == 28) {
				$this->AddPage("L");  
				for($i=0;$i<count($header);$i++) $this->Cell($w[$i],7,$header[$i],1,0,'C'); $this->Ln();
				$c = 0;
				$t = "T";
			}
			$this->Cell($w[0],6,$row[0],"LR$t$b",0,'L',$fill);
			$this->Cell($w[1],6,$row[1],"LR$t$b",0,'L',$fill);
			$this->Cell($w[2],6,substr($row[2],0,117),"LR$t$b",0,'L',$fill);
			$this->Cell($w[3],6,number_format($row[3],5),"LR$t$b",0,'R',$fill);
			$this->Ln();
			$fill = !$fill;
			$t="";
			$c++;
		}
		$this->Cell(array_sum($w),0,'','T');
	}
}
$pdf = new PDF();
$pdf->SetFont('Courier','',16);
$pdf->AddPage("L");
$pdf->Text(60,60,"***** TWITTELEGRAM.COM *****");
$pdf->Text(60,80,"Detailed Activity Report");
$pdf->Text(60,100, "User: ".json_decode($_SESSION["telegram_auth_data"],true)["first_name"]." ".json_decode($_SESSION["telegram_auth_data"],true)["last_name"]);
$pdf->Text(60,120,"Telegram ID: ".json_decode($_SESSION["telegram_auth_data"],true)["id"]);
$pdf->Text(60,140,"Period: ".$_GET["F"]." ".$_GET["Y"]);
$data = $_SESSION["pdfs"][$_GET["Y"]][$_GET["F"]];
$header = array("Date and time","Category","Description","Weight");
$temp = tmpfile(); foreach($data as $row) fwrite($temp,implode(";",$row)."\n"); 
$data = $pdf->LoadData(stream_get_meta_data($temp)['uri']);
$pdf->LoadData($temp);
$pdf->SetFont('Courier','',8);
$pdf->AddPage("L");
$pdf->ImprovedTable($header,$data);
$pdf->Output("I",$_GET["F"]." ".$_GET["Y"].".pdf");
?>