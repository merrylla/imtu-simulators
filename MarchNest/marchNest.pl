#!/usr/bin/perl

use strict;

use vars qw (
   $perlDir $SysHome $pgm
   $AppName $configFile %Ini %Parm $rc
   %RequiredTags $SqlLiteDbFile
   $FailedResultCode $FailedResultText
);

($pgm  = $0) =~ s-.*/-- ;

if(!defined($ENV{'SYSHOME'})) {
   $SysHome = "/u/debit";
} else {
   $SysHome = $ENV{'SYSHOME'};
}

$perlDir = "$SysHome/lib/perl";
if(!-d $perlDir) {
   print STDERR "$perlDir not found, exiting\n";
   exit(1);
}
push(@INC, $perlDir);

require 'perlSlog.pl';
require DBI;
use XML::Simple;
use POSIX;
use Config::IniFiles;
use Dancer;

#set serializer => 'XML';
set serializer => 'JSON';

$| = 1;

$RequiredTags{"AppName"} = 1;
$RequiredTags{"SqlLiteDbFile"} = 1;

sub usage
{
   print STDERR "\nusage: $pgm [-h]\n";

   print STDERR "\n\n";
   print STDERR "-h                 Displays help message\n";
   print STDERR "\n\n";

   exit(2);
}

sub loadConfig
{
   my($filnm) = @_;
   my($me) = "loadConfig";

   tie %Ini, 'Config::IniFiles', ( -file => $filnm );

   $AppName = $Ini{"Common"}{"AppName"};

   $SqlLiteDbFile = $Ini{"Common"}{"SqlLiteDbFile"};

   if(defined($Ini{"Common"}{"FailedResultCode"})) {
      $FailedResultCode = $Ini{"Common"}{"FailedResultCode"};
   } else {
      $FailedResultCode = 999;
   }

   if(defined($Ini{"Common"}{"FailedResultText"})) {
      $FailedResultText = $Ini{"Common"}{"FailedResultText"};
   } else {
      $FailedResultCode = "Parameter check mismatch";
   }

   &slog("IL_DB4", "$me: AppName = '$AppName'");
   &slog("IL_DB4", "$me: SqlLiteDbFile = '$SqlLiteDbFile'");
   &slog("IL_DB4", "$me: FailedResultCode = '$FailedResultCode'");
   &slog("IL_DB4", "$me: FailedResultText = '$FailedResultText'");
}

sub validateConfig
{
   my($tag);
   my($rc);
   my($errCount);

   $errCount = 0;

   foreach $tag (keys %RequiredTags) {
      $rc = &checkTag($tag);
      if($rc != 1) {
         print STDERR "Tag '$tag' not in [Common]\n";

         &slog("IL_CRIT", "Tag '$tag' not defined in [Common]");

         $errCount++;
      }
   }

   if($errCount == 0) {
      return(1);
   } else {
      return(undef);
   }
}

sub checkTag
{
   my($tag) = @_;

   if(defined($Ini{"Common"}{$tag})) {
      return(1);
   } else {
      return(undef);
   }
}

sub readDb
{
   my($app, $key) = @_;
   my($me) = "readDb";
   my($dbh);
   my($sth);
   my($sql);
   my($xmlData);

   &slog("IL_DB4", "$me: app = '$app', key = '$key'");

   $dbh = DBI->connect("dbi:SQLite:dbname=$SqlLiteDbFile",
                       "", "", { RaiseError => 0,
                                 AutoCommit => 0 });

   if(!($dbh)) {
      print STDERR "Could not open DB file '$SqlLiteDbFile', exiting\n";
      &slog("IL_ERR", "Could not open DB file '$SqlLiteDbFile', exiting");
      exit(3);
   }

   $sql = "select xml from xmlData where app = '$app' and key = '$key'";

   $sth = $dbh->prepare($sql);

   $sth->execute();

   $xmlData = $sth->fetchrow_array();
   &slog("IL_DB4", "$me: xmlData = '$xmlData'");

   $sth->finish;
   $dbh->disconnect;

   return($xmlData);
}


sub getParms
{
   my($appName, $key) = @_;
   my($me) = "getParms";
   my($dbBuf);
   my($dbXml);
   my($tag);
   my($val);
   my($i);

   $dbBuf = &readDb($AppName, "$key");

   $dbXml = eval { XMLin($dbBuf) };
   if($@) {
      &slog("IL_ERR", "$me: XMLin() call failed '$@'");
      return;
   }

   undef(%Parm);
   for($i = 0; $i <= 20; $i++) {
      $tag = sprintf("a%d", $i);
      $val = eval { $dbXml->{$tag} };
      if(! $@) {
         $Parm{$tag} = $val;
      }
   }

   $val = eval { $dbXml->{'delay'} };
   if(! $@) {
      $val =~ tr/a-z/A-Z/;
      $Parm{'delay'} = $val;
   }

   $val = eval { $dbXml->{'abort'} };
   if(! $@) {
      $val =~ tr/a-z/A-Z/;
      $Parm{'abort'} = $val;
   }
}

sub logAssocArray
{
   my(%arr) = @_;
   my($me) = "logAssocArray";
   my($key);
   my($val);

   foreach $key (keys(%arr)) {
      $val = $arr{$key};
      slog("IL_DB4", "$me: '$key' => '$val'");
   }
}

############################################################################
#
#  Dancer request handlers
#
############################################################################
get '/ws_recharge_credit' => sub {
   my($me) = "ws_recharge_credit";
   my(%resp);
   my($errCnt);

   my($code_utilisateur);
   my($password_actuel);
   my($telephone_beneficiare);
   my($montant_recharge);
   my($numero_transaction);

   $errCnt = 0;

   $code_utilisateur = params->{code_utilisateur};
   $password_actuel = params->{password_actuel};
   $telephone_beneficiare = params->{telephone_beneficiare};
   $montant_recharge = params->{montant_recharge};
   $numero_transaction = params->{numero_transaction};

   &slog("IL_DB4", "$me: code_utilisateur ='$code_utilisateur'");
   &slog("IL_DB4", "$me: password_actuel ='$password_actuel'");
   &slog("IL_DB4", "$me: telephone_beneficiare ='$telephone_beneficiare'");
   &slog("IL_DB4", "$me: montant_recharge ='$montant_recharge'");
   &slog("IL_DB4", "$me: numero_transaction ='$numero_transaction'");

   &getParms($AppName, $telephone_beneficiare);

   if(defined($Parm{"a0"})) {
      if($Parm{"a0"} ne $code_utilisateur) {
         &slog("IL_ERR", "$me: code_utilisateur - '$Parm{'a0'}' not equal to " .
               "'$code_utilisateur'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a1"})) {
      if($Parm{"a1"} ne $password_actuel) {
         &slog("IL_ERR", "$me: password_actuel - '$Parm{'a1'}' not equal to " .
               "'$password_actuel'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a2"})) {
      if($Parm{"a2"} ne $telephone_beneficiare) {
         &slog("IL_ERR", "$me: telephone_beneficiare - '$Parm{'a2'}' " .
               "not equal to '$telephone_beneficiare'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a3"})) {
      if($Parm{"a3"} ne $montant_recharge) {
         &slog("IL_ERR", "$me: montant_recharge - '$Parm{'a3'}' not equal to " .
               "'$montant_recharge'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a4"})) {
      if($Parm{"a4"} ne $numero_transaction) {
         &slog("IL_ERR", "$me: numero_transaction - '$Parm{'a4'}' " .
               "not equal to '$numero_transaction'");
         $errCnt++;
      }
   }

   if(defined($Parm{'delay'})) {
      if($Parm{'delay'} > 0) {
         &slog("IL_DB4", "$me: delay defined, sleep for $Parm{'delay'} " .
               "seconds");
         sleep($Parm{'delay'});
      }
   }
   
   if(defined($Parm{'abort'})) {
      if($Parm{'abort'} eq "TRUE") {
         &slog("IL_DB4", "$me: abort defined, exit");
         exit(4);
      }
   }

   if($errCnt > 0) {
      $resp{"result_ok"} = $FailedResultCode;
      $resp{"info_message"} = $FailedResultText;
      $resp{"short_message_error"} = $FailedResultText;
      $resp{"type_resultat"} = $FailedResultCode;
   } else {
      $resp{"result_ok"} = $Parm{'a5'};
      $resp{"info_message"} = $Parm{'a6'};
      $resp{"short_message_error"} = $Parm{'a7'};
      $resp{"type_resultat"} = $Parm{'a8'};
   }


   if(defined($Parm{'a9'})) {
      $resp{"numero_transaction"} = $Parm{'a9'};
   } else {
      $resp{"numero_transaction"} = $numero_transaction;
   }

   &logAssocArray(%resp);

   return(\%resp);
};

get '/ws_get_solde_credit' => sub {
   my($me) = "ws_get_solde_credit";
   my(%resp);
   my($errCnt);

   my($code_utilisateur);
   my($password_actuel);

   $errCnt = 0;

   $code_utilisateur = params->{code_utilisateur};
   $password_actuel = params->{password_actuel};

   &slog("IL_DB4", "$me: code_utilisateur ='$code_utilisateur'");
   &slog("IL_DB4", "$me: password_actuel ='$password_actuel'");

   &getParms($AppName, $code_utilisateur);

   if(defined($Parm{"a0"})) {
      if($Parm{"a0"} ne $code_utilisateur) {
         &slog("IL_ERR", "$me: code_utilisateur - '$Parm{'a0'}' not equal to " .
               "'$code_utilisateur'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a1"})) {
      if($Parm{"a1"} ne $password_actuel) {
         &slog("IL_ERR", "$me: password_actuel - '$Parm{'a1'}' not equal to " .
               "'$password_actuel'");
         $errCnt++;
      }
   }

   if(defined($Parm{'delay'})) {
      if($Parm{'delay'} > 0) {
         &slog("IL_DB4", "$me: delay defined, sleep for $Parm{'delay'} " .
               "seconds");
         sleep($Parm{'delay'});
      }
   }
   
   if(defined($Parm{'abort'})) {
      if($Parm{'abort'} eq "TRUE") {
         &slog("IL_DB4", "$me: abort defined, exit");
         exit(5);
      }
   }

   if($errCnt > 0) {
      $resp{"result_ok"} = $FailedResultCode;
      $resp{"info_message"} = $FailedResultText;
      $resp{"short_message_error"} = $FailedResultText;
      $resp{"type_resultat"} = $FailedResultCode;
   } else {
      $resp{"result_ok"} = $Parm{'a2'};
      $resp{"info_message"} = $Parm{'a3'};
      $resp{"short_message_error"} = $Parm{'a4'};
      $resp{"type_resultat"} = $Parm{'a5'};
   }

   $resp{"numero_transaction"} = $Parm{'a6'};

   &logAssocArray(%resp);

   return(\%resp);
};

get '/ws_liste_transaction' => sub {
   my($me) = "ws_liste_transaction";
   my(%resp);
   my($errCnt);

   my($code_utilisateur);
   my($password_actuel);
   my($date_debut);
   my($date_fin);

   $errCnt = 0;

   $code_utilisateur = params->{code_utilisateur};
   $password_actuel = params->{password_actuel};
   $date_debut = params->{date_debut};
   $date_fin = params->{date_fin};

   &slog("IL_DB4", "$me: code_utilisateur ='$code_utilisateur'");
   &slog("IL_DB4", "$me: password_actuel ='$password_actuel'");
   &slog("IL_DB4", "$me: date_debut ='$date_debut'");
   &slog("IL_DB4", "$me: date_fin ='$date_fin'");

   &getParms($AppName, "LIST-$code_utilisateur");

   if(defined($Parm{"a0"})) {
      if($Parm{"a0"} ne $code_utilisateur) {
         &slog("IL_ERR", "$me: code_utilisateur - '$Parm{'a0'}' not equal to " .
               "'$code_utilisateur'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a1"})) {
      if($Parm{"a1"} ne $password_actuel) {
         &slog("IL_ERR", "$me: password_actuel - '$Parm{'a1'}' not equal to " .
               "'$password_actuel'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a2"})) {
      if($Parm{"a2"} ne $date_debut) {
         &slog("IL_ERR", "$me: date_debut - '$Parm{'a2'}' " .
               "not equal to '$date_debut'");
         $errCnt++;
      }
   }

   if(defined($Parm{"a3"})) {
      if($Parm{"a3"} ne $date_fin) {
         &slog("IL_ERR", "$me: date_fin - '$Parm{'a3'}' " .
               "not equal to '$date_fin'");
         $errCnt++;
      }
   }

   if(defined($Parm{'delay'})) {
      if($Parm{'delay'} > 0) {
         &slog("IL_DB4", "$me: delay defined, sleep for $Parm{'delay'} " .
               "seconds");
         sleep($Parm{'delay'});
      }
   }
   
   if(defined($Parm{'abort'})) {
      if($Parm{'abort'} eq "TRUE") {
         &slog("IL_DB4", "$me: abort defined, exit");
         exit(6);
      }
   }

   if($errCnt > 0) {
      $resp{"reference"} = "";
      $resp{"date_operation"} = "";
      $resp{"montant_recharge"} = "";
      $resp{"telephone_beneficiare"} = "";
      $resp{"statut_operation"} = "";
      $resp{"create_by"} = "";
   } else {
      $resp{"reference"} = $Parm{'a4'};
      $resp{"date_operation"} = $Parm{'a5'};
      $resp{"montant_recharge"} = $Parm{'a6'};
      $resp{"telephone_beneficiare"} = $Parm{'a7'};
      $resp{"statut_operation"} = $Parm{'a8'};
      $resp{"create_by"} = $Parm{'a9'};
   }

   &logAssocArray(%resp);

   return(\%resp);
};

get '/quit' => sub {
   my($me) = "quit";
   &slog("IL_DB4", "$me: /quit called, exiting");
   exit(7);
};

get '/exit' => sub {
   my($me) = "exit";
   &slog("IL_DB4", "$me: /exit called, exiting");
   exit(7);
};


############################################################################
#
#  Main starts here
#
############################################################################

&sysinit($pgm, "");

$configFile = "marchNest.cfg";

if(-r $configFile ) {
   &loadConfig($configFile);
   $rc = &validateConfig();
   if($rc != 1) {
      exit(9);
   }
} else {
   print STDERR "\nCan't run without $configFile\n";
   exit(10);
}

if($#ARGV != -1) {
   print STDERR "Wrong number of parameters specified\n";
   &usage();
}

&slog("IL_DB4", "$pgm started");

dance;
