<?php

$command_descriptions = array(
	240=>'End sub-negotiation',
	241=>'No operation',
	242=>'Data Mark',
	243=>'Break',
	244=>'Suspend',
	245=>'Abort output',
	246=>'Are you there?',
	247=>'Erase char',
	248=>'Erase line',
	249=>'Go ahead',
	250=>'Being subnegotiation',
	251=>'WILL',
	252=>'WONT',
	253=>'DO',
	254=>'DONT',
	255=>'IAC');
	
$commands = array(
	'SE'=>0xF0,	'NOP'=>0xF1, 'DM'=>0xF2, 'BRK'=>0xF3, 'INT'=>0xF4, 'ABR'=>0xF5,
	'AYT'=>0xF6, 'EC'=>0xF7, 'EL'=>0xF8, 'GA'=>0xF9, 'SB'=>0xFA, 'WILL'=>0xFB,
	'WONT'=>0xFC, 'DO'=>0xFD, 'DONT'=>0xFE, 'IAC'=>0xFF);	
	
$option_descriptions = array(
	0=>'Binary Xmit',
	1=>'Echo Data', 
	2=>'Reconnect',
	3=>'Supress GA',
	4=>'Message sz',
	5=>'Opt Status',
	6=>'Timing Mark',
	7=>'R/C XmtEcho',
	8=>'Line Width',
	9=>'Page Length',
	10=>'CR Use',
	11=>'Horiz Tabs',
	12=>'Hor Tab Use',
	13=>'FF Use',
	14=>'Vert Tabs',
	15=>'Ver Tab Use',
	16=>'Lf Use',
	17=>'Ext ASCII',
	18=>'Logout',
	19=>'Byte Macro',
	20=>'Data Term',
	21=>'SUPDUP',
	22=>'SUPDUP Outp',
	23=>'Send Locate',
	24=>'Term Type',
	25=>'End Record',
	26=>'TACACS ID',
	27=>'Output Mark',
	28=>'Term Loc#',
	29=>'3270 Regime',
	30=>'X.3 PAD',
	31=>'Window Size',
	32=>'Term Speed',
	33=>'Remote Flow',
	34=>'Linemode',
	35=>'X-Display-Location',
	36=>'Environ',
	37=>'Authentication',
	38=>'Encrypt',
	39=>'New-Environ',
	40=>'TN3270E',
	42=>'Charset',
	44=>'COM-Port-Option',
	255=>'Extended');
	
$options = array(
	'ECHO'=>0x01, 'ENCRYPT'=>0x26
);

?>