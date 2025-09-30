<?php

require_once __DIR__ . '/../models/POSChargeHelper.php';

$posHelper = new POSChargesHelper();

// Step 1: sync charges for this guest
$posHelper->syncAllPOSCharges($guestId);

// Step 2: get unpaid charges for invoice preview
$unpaidCharges = $posHelper->getUnpaidChargesByGuest($guestId);

// Step 3: when invoice is finalized
$posHelper->attachChargesToInvoice($guestId, $invoiceId);
