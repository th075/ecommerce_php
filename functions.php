<?php
use TH\model\User;
use TH\model\Cart;

function formatPrice($vlprice) {

	if(!$vlprice > 0) $vlprice = 0;
	return number_format($vlprice, 2, ",", ".");
}

function formatDate($date){
	return date('d/m/Y', strtotime($date));
}

function checkLogin($inadmin = true) {
	return User::checkLogin($inadmin);
}

function getUserName() {     
    $user = User::getFromSession();     
    $user->get($user->getiduser());      
    return $user->getdesperson();
}

function getCartNrQtd(){
	$cart = Cart::getFromSession();
	$totals = $cart->getProductsTotals();
	return ($totals['nrqtd']);
}

function getCartVlSubtotal(){
	$cart = Cart::getFromSession();
	$totals = $cart->getProductsTotals();
	return formatPrice($totals['vlprice']);
}
?>