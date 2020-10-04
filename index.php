<?php 
session_start();
require_once("vendor/autoload.php");
require_once("functions.php");

use \Slim\Slim;
use TH\Page;
use TH\PageAdmin;
use TH\model\User;
use TH\model\Category;
use TH\model\Product;
use TH\model\Cart;
use TH\model\Address;
use TH\model\Order;
use TH\model\OrderStatus;

$app = new \Slim\Slim();

$app->config('debug', true);

$app->get('/', function() {
    
	/*$sql = new Hcode\DB\sql();          Esse codigo foi uma simples consulta que fiz no banco
	$results = $sql->select("SELECT * FROM tb_users");
	echo json_encode($results);
	*/
	$products = Product::listAll();
	$page = new Page();
	$page->setTpl("index", [
		"products"=>Product::checkList($products)
	]);
	
});

$app->get('/admin',function() {
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("index");
});

$app->get('/admin/login',function(){
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("login");

});

$app->post('/admin/login',function(){

	User::login($_POST["login"],$_POST["password"]);
	header("Location: /admin");
	exit;
});

$app->get('/admin/logout',function(){
	User::logOut();
	header("Location: /admin/login");
	exit;
});

$app->get('/admin/users', function(){
	User::verifyLogin();
	$search = (isset($_GET['search'])) ? $_GET['search'] : "" ;
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	if ($search != ''){
		$pagination = User::getPageSearch($search,$page, 5);
	}else{
		$pagination = User::getPage($page);
	}

	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i++){
		array_push($pages, [
			'href'=>'/admin/users?'.http_build_query([
				'page'=>$i+1,
				'search'=>$search
			]),
			"text"=>$i+1
		]);
	}

	$page = new PageAdmin();
	$page->setTpl("users", array(
		"users" => $pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	));
});

$app->get('/admin/users/:iduser/password', function($iduser){
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$page = new PageAdmin();
	$page->setTpl('users-password',[
		'user'=>$user->getValues(),
		'msgError'=>$user->getError(),
		'msgSuccess'=>$user->getSuccess()
	]);
});

$app->post('/admin/users/:iduser/password', function($iduser){
	User::verifyLogin();

	if(!isset($_POST['despassword']) || $_POST['despassword'] === ''){
		User::setError('Preencha a nova senha.');
		header("Location: /admin/users/".$iduser."/password");
		exit;
	}

	if(!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === ''){
		User::setError('Preencha a confirmação da nova senha.');
		header("Location: /admin/users/".$iduser."/password");
		exit;
	}

	if($_POST['despassword'] !== $_POST['despassword-confirm']){
		User::setError('Confirme corretamente as senhas.');
		header("Location: /admin/users/".$iduser."/password");
		exit;
	}

	$user = new User();
	$user->get((int)$iduser);
	$user->setdespassword(User::getPasswordHash($_POST['despassword']));
	$user->update();
	User::setSuccess('Senha alterada com sucesso.');
	header("Location: /admin/users/".$iduser."/password");
	exit;

});



$app->get('/admin/users/create', function(){
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl('users-create');
});

$app->get('/admin/users/:iduser/delete', function($iduser){
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$user->delete();
	header("Location: /admin/users");
	exit;

});


$app->get('/admin/users/:iduser', function($iduser){ 
 
   User::verifyLogin();
 
   $user = new User();
 
   $user->get((int)$iduser);
 
   $page = new PageAdmin();
 
   $page ->setTpl("users-update", array(
        "user"=>$user->getValues()
    ));
 
});



$app->post('/admin/users/create', function(){
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
     "cost"=>12
 ]);
	$user->setData($_POST);
	$user->save();
	header("Location: /admin/users");
	exit;
	});

$app->post('/admin/users/:iduser', function($iduser){
	User::verifyLogin();
	$user = new User();
	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();
	header("Location: /admin/users");
	exit;

});

$app->get('/admin/forgot', function(){
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot");
});

$app->post('/admin/forgot', function(){
	$user = User::getForgot($_POST["email"]);
	header("Location: /admin/forgot/sent");
	exit;
});

$app->get('/admin/forgot/sent', function(){
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-sent");
});

$app->get('/admin/forgot/reset', function(){
	$user = User::validForgotDecrypt($_GET["code"]);
	$page = new PageAdmin([
	"header"=>false,
	"footer"=>false
	]);

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});

$app->post('/admin/forgot/reset', function(){
	$forgot = User::validForgotDecrypt($_POST["code"]);
	User::setForgotUsed($forgot["idrecovery"]);
	$user = new User();
	$user->get((int)$forgot["iduser"]);
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);
	$user->setPassword($password);
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);
	$page->setTpl("forgot-reset-success");
});

$app->get('/admin/categories', function(){
	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "" ;
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	if ($search != ''){
		$pagination = Category::getPageSearch($search,$page, 5);
	}else{
		$pagination = Category::getPage($page);
	}

	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i++){
		array_push($pages, [
			'href'=>'/admin/categories?'.http_build_query([
				'page'=>$i+1,
				'search'=>$search
			]),
			"text"=>$i+1
		]);
	}

	$page = new PageAdmin();
	$page->setTpl("categories",[
		"categories"=> $pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	]);
});

$app->get('/admin/categories/create', function(){
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("categories-create");
});

$app->post('/admin/categories/create', function(){
	User::verifyLogin();
	$category = new Category();
	$category->setData($_POST);
	$category->save();
	header("Location: /admin/categories");
	exit;
});

$app->get('/admin/categories/:idcategory/delete',function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$category->delete();
	header("Location: /admin/categories");
	exit;
});

$app->get('/admin/categories/:idcategory', function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$page = new PageAdmin();
	$page->setTpl("categories-update", [
		"category"=>$category->getValues()
	]);
});

$app->post('/admin/categories/:idcategory', function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$category->setData($_POST);
	$category->save();
	header("Location: /admin/categories");
	exit;
});

$app->get('/categories/:idcategory',function($idcategory){
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	$category = new Category();
	$category->get((int)$idcategory);
	$pagination = $category->getProductsPage($page);
	$pages = [];
	for ($i=1; $i <= $pagination['pages']; $i++) { 
		array_push($pages,[
		"link"=>'/categories/'.$category->getidcategory()."?page=".$i,
		"page"=>$i
		]);
	}
	$page = new Page();
	$page->setTpl("category",[
		'category'=>$category->getValues(),
		'products'=>$pagination['data'],
		"pages"=>$pages
	]);
});

$app->get('/admin/products', function(){
	User::verifyLogin();
	
	$search = (isset($_GET['search'])) ? $_GET['search'] : "" ;
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	if ($search != ''){
		$pagination = Product::getPageSearch($search,$page, 5);
	}else{
		$pagination = Product::getPage($page);
	}

	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i++){
		array_push($pages, [
			'href'=>'/admin/products?'.http_build_query([
				'page'=>$i+1,
				'search'=>$search
			]),
			"text"=>$i+1
		]);
	}

	$page = new PageAdmin();
	$page->setTpl("products",[
		"products"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	]);

});

$app->get('/admin/products/create', function(){
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("products-create");
});

$app->post('/admin/products/create',function(){
	User::verifyLogin();
	$page = new Product();
	$page->setData($_POST);
	$page->save();
	header("Location: /admin/products");
	exit;
});

$app->get('/admin/products/:idproduct', function($idproduct){
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$page = new PageAdmin();
	$page->setTpl("products-update",[
		"product"=>$product->getValues()
	]);

});

$app->post('/admin/products/:idproduct', function($idproduct){
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$product->setData($_POST);
	$product->save();
	if($_FILES["file"]["name"] !== "") $product->setPhoto($_FILES["file"]);
	header("Location: /admin/products");
	exit;
});

$app->get('/admin/products/:idproduct/delete', function($idproduct){
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$product->delete();
	header("Location: /admin/products");
	exit;

});

$app->get('/admin/categories/:idcategory/products', function($idcategory){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$page = new PageAdmin();
	$page->setTpl('categories-products',[
		"category"=>$category->getValues(),
		"productsRelated"=>$category->getProducts(),
		"productsNotRelated"=>$category->getProducts(false)
	]);
});

$app->get('/admin/categories/:idcategory/products/:idproduct/add', function($idcategory, $idproduct){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$product = new Product();
	$product->get((int)$idproduct);
	$category->addProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;
});

$app->get('/admin/categories/:idcategory/products/:idproduct/remove', function($idcategory, $idproduct){
	User::verifyLogin();
	$category = new Category();
	$category->get((int)$idcategory);
	$product = new Product();
	$product->get((int)$idproduct);
	$category->removeProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;
});

$app->get('/products/:desurl', function($desurl){
	$product = new Product();
	$product->getFromUrl($desurl);
	$page = new Page();
	$page->setTpl('product-detail',[
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
});

$app->get("/cart", function(){
	$cart = Cart::getFromSession();
	$page = new Page();
	$page->setTpl("cart",[
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});

$app->get("/cart/:idproduct/add", function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;
	for ($i = 0; $i < $qtd; $i++) {
		
		$cart->addProduct($product);
	}
	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/minus", function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProduct($product);
	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/remove", function($idproduct){
	$product = new Product();
	$product->get((int)$idproduct);
	$cart = Cart::getFromSession();
	$cart->removeProduct($product, true);
	header("Location: /cart");
	exit;
});

$app->post("/cart/freight", function(){
	$cart = Cart::getFromSession();
	$cart->setFreight($_POST['zipcode']);
	header("Location: /cart");
	exit;
});

$app->get("/checkout",function(){
	User::verifyLogin(false);
	$address = new Address();
	$cart = Cart::getFromSession();

	if (isset($_GET['zipcode'])){
	$address->loadFromCEP($_GET['zipcode']);
	$cart->setdeszipcode($_GET['zipcode']);
	$cart->save();
	$cart->getCalculateTotal();
	}

	if(!$address->getdesaddress()) $address->setdesaddress('');
	if(!$address->getdesnumber()) $address->setdesnumber('');
	if(!$address->getdescomplement()) $address->setdescomplement('');
	if(!$address->getdesdistrict()) $address->setdesdistrict('');
	if(!$address->getdescity()) $address->setdescity('');
	if(!$address->getdesstate()) $address->setdesstate('');
	if(!$address->getdescountry()) $address->setdescountry('');
	if(!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();
	$page->setTpl('checkout',[
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);
});

$app->post("/checkout",function(){
	User::verifyLogin(false);
	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === ""){
		Address::setMsgError("Informe o CEP.");
		header("Location: /checkout");
		exit;
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === "  "){
	Address::setMsgError("Informe o endereço.");
	header("Location: /checkout");
	exit;
	}

	if (!isset($_POST['desnumber']) || $_POST['desnumber'] === ""){
	Address::setMsgError("Informe o numero.");
	header("Location: /checkout");
	exit;
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === ""){
	Address::setMsgError("Informe o bairro.");
	header("Location: /checkout");
	exit;
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === ""){
	Address::setMsgError("Informe o cidade.");
	header("Location: /checkout");
	exit;
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === ""){
	Address::setMsgError("Informe o estado.");
	header("Location: /checkout");
	exit;
	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === ""){
	Address::setMsgError("Informe o país.");
	header("Location: /checkout");
	exit;
	}

	$user = User::getFromSession();
	$address = new Address();
	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();
	$address->setData($_POST);
	$address->save();
	$cart = Cart::getFromSession();
	$totals = $cart->getCalculateTotal();
	$order = new Order();
	$order->setData([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);
	$order->save();
	//header("Location: /order/".$order->getidorder());
	switch ((int)$_POST['payment-method']) {
		case 1:
			header("Location: /order/".$order->getidorder()."/pagseguro");    // (Integração pagseguro)
			break;
		
		case 2:
			header("Location: /order/".$order->getidorder()."/paypal");
			break;
	}
	    
	exit;
});

$app->get('/order/:idorder/pagseguro', function($idorder){
	User::verifyLogin(false);
	$page = new Page([
		"header"=>false,
		"footer"=>false
	]);
	$order = new Order();
	$order->get((int)$idorder);
	$cart = $order->getCart();
	$page->setTpl('payment-pagseguro',[
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'phone'=>[
			'areaCode'=>substr($order->getnrphone(), 0, 2),
			'number'=>substr($order->getnrphone(), 2, strlen($order->getnrphone()))
		]
	]);
});

$app->get('/order/:idorder/paypal', function($idorder){
	User::verifyLogin(false);
	$page = new Page([
		"header"=>false,
		"footer"=>false
	]);
	$order = new Order();
	$order->get((int)$idorder);
	$cart = $order->getCart();
	$page->setTpl('payment-paypal',[
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
	]);

});


$app->get("/login", function(){
	$page = new Page();
	$page->setTpl('login',[
		"error"=>User::getError(),
		"errorRegister"=>User::getErrorRegister(),
		"registerValues"=>(isset($_SESSION["registerValues"])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
	]);
});

$app->post("/login", function(){
	try{
	User::Login($_POST['login'], $_POST['password']);
	}catch(Exception $e){
		User::setError($e->getMessage());
		header("Location: /login");
		exit;
	}
	header("Location: /");
	exit;
});

$app->get('/logout',function(){
	User::logOut();
	Cart::removeToSession(); //Metodo estatico criado na classe Cart para corrigir bug de carrinho salvo em login diferente
	session_regenerate_id(); //Essa função tambem corrige o bug do carrinho na sessão
	header("Location: /login");
	exit;
});

$app->post("/register", function(){

	$_SESSION['registerValues'] = $_POST;
	
	if (!isset($_POST['name']) || $_POST['name'] == ''){

		User::setErrorRegister("Preencha o seu nome. ");
		header("Location: /login");
		exit;
	}

	if (!isset($_POST['email']) || $_POST['email'] == ''){

		User::setErrorRegister("Preencha o seu e-mail. ");
		header("Location: /login");
		exit;
	}

	if (!isset($_POST['password']) || $_POST['password'] == ''){

		User::setErrorRegister("Preencha a sua senha. ");
		header("Location: /login");
		exit;
	}

	if (User::checkLoginExist($_POST['email']) === true){
		User::setErrorRegister("Este endereço de e-mail já está sendo usado por outro usuário.");
		header("Location: /login");
		exit;
	}
	
	$_SESSION['registerValues'] = NULL;
	

	$user = new User();
	
	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>User::getPasswordHash($_POST['password']),
		'nrphone'=>(int)$_POST['phone']
	]);
	$user->save();

	User::login($_POST['email'], $_POST['password']);
	header('Location: /');
	exit;

});
$app->get('/forgot', function(){
	$page = new Page();
	$page->setTpl("forgot");
});

$app->post('/forgot', function(){
	$user = User::getForgot($_POST["email"],false);
	header("Location: /forgot/sent");
	exit;
});

$app->get('/forgot/sent', function(){
	$page = new Page();
	$page->setTpl("forgot-sent");
});

$app->get('/forgot/reset', function(){
	$user = User::validForgotDecrypt($_GET["code"]);
	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));
});

$app->post('/forgot/reset', function(){
	$forgot = User::validForgotDecrypt($_POST["code"]);
	User::setForgotUsed($forgot["idrecovery"]);
	$user = new User();
	$user->get((int)$forgot["iduser"]);
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);
	$user->setPassword($password);
	$page = new Page();
	$page->setTpl("forgot-reset-success");
});

$app->get("/profile", function(){
	User::verifyLogin(false);
	$user = User::getFromSession();
	$page = new Page();
	$page->setTpl("profile", [
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getError()
	]);
});

$app->post("/profile", function(){
	User::verifyLogin(false);

	if(!isset($_POST['desperson']) || $_POST['desperson'] === ''){
		User::setError("Preencha o seu nome.");
		header('Location: /profile');
		exit;
	}

	if(!isset($_POST['desemail']) || $_POST['desemail'] === ''){
		User::setError("Preencha o seu e-mail.");
		header('Location: /profile');
		exit;
	}

	$user = User::getFromSession();

	if($_POST['desemail'] !== $user->getdesemail()){
		if(User::checkLoginExist($_POST['desemail']) === true){
			User::setError("Este endereço de e-mail já está cadastrado");
		}

	}
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail'];
	$user->setData($_POST);
	$user->update();
	User::setSuccess("Dados alterados com sucesso!");
	header('Location: /profile');
	exit;
});

$app->get('/order/:idorder', function($idorder){
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);
	$page = new Page();
	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);
});

$app->get('/boleto/:idorder', function($idorder){
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
$dias_de_prazo_para_pagamento = 10;
$taxa_boleto = 5.00;
$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 
$valor_cobrado = $order->getvltotal(); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
$valor_cobrado = str_replace(",", ".",$valor_cobrado);
$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

// DADOS DO SEU CLIENTE
$dadosboleto["sacado"] = $order->getdesperson();
$dadosboleto["endereco1"] = $order->getdesaddress() . " - CEP: " . $order->getdesdistrict();
$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " " . $order->descountry() . " , " . $order->getdeszipcode();

// INFORMACOES PARA O CLIENTE
$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
$dadosboleto["demonstrativo3"] = "";
$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
$dadosboleto["quantidade"] = "";
$dadosboleto["valor_unitario"] = "";
$dadosboleto["aceite"] = "";		
$dadosboleto["especie"] = "R$";
$dadosboleto["especie_doc"] = "";


// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


// DADOS DA SUA CONTA - ITAÚ
$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

// DADOS PERSONALIZADOS - ITAÚ
$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

// SEUS DADOS
$dadosboleto["identificacao"] = "Hcode Treinamentos";
$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

// NÃO ALTERAR!
$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
require_once($path . "funcoes_itau.php");
require_once($path . "layout_itau.php");

});

$app->get('/profile/orders', function(){
	User::verifyLogin(false);
	$user = User::getFromSession();
	$page = new Page();
	$page->setTpl('profile-orders',[
		'orders'=>$user->getOrders()
	]);

});

$app->get('/profile/orders/:idorder', function($idorder){
	User::verifyLogin(false);
	$order = new Order();
	$order->get((int)$idorder);
	$cart = new Cart();
	$cart->get((int)$order->getidcart());
	$cart->getCalculateTotal();
	$page = new Page();
	$page->setTpl('profile-orders-detail',[
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);
});

$app->get('/profile/change-password', function(){
	User::verifyLogin(false);
	$page = new Page();
	$page->setTpl('profile-change-password',[
		'changePassError'=>User::getError(),
		'changePassSuccess'=>User::getSuccess()
	]);

});

$app->post('/profile/change-password', function(){
	User::verifyLogin(false);
	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === ''){
		User::setError('Digite a senha atual.');
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === ''){
		User::setError('Digite a nova senha.');
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === ''){
		User::setError('Confirme a nova senha.');
		header("Location: /profile/change-password");
		exit;
	}

	if (($_POST['current_pass']) === $_POST['new_pass']){
		User::setError('A sua nova senha deve ser diferente da atual.');
		header("Location: /profile/change-password");
		exit;
	}

	$user = User::getFromSession();

	if(!password_verify($_POST['current_pass'], $user->getdespassword())){
		User::setError('A senha esta inválida.');
		header("Location: /profile/change-password");
		exit;
	}

	$user->setdespassword(User::getPasswordHash($_POST['new_pass']));
	$user->update();
	User::setSuccess("Senha alterada com sucesso.");
	header("Location: /profile/change-password");
	exit;
});

$app->get('/admin/orders', function(){
	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "" ;
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	if ($search != ''){
		$pagination = Order::getPageSearch($search,$page, 5);
	}else{
		$pagination = Order::getPage($page);
	}

	$pages = [];
	for ($i = 0; $i < $pagination['pages']; $i++){
		array_push($pages, [
			'href'=>'/admin/orders?'.http_build_query([
				'page'=>$i+1,
				'search'=>$search
			]),
			"text"=>$i+1
		]);
	}

	$page = new PageAdmin();
	$page->setTpl('orders',[
		"orders"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	]);
});

$app->get('/admin/orders/:idorder/delete', function($idorder){
	User::verifyLogin();
	$order = new Order();
	$order->get((int)$idorder);
	$order->delete();
	header("Location: /admin/orders");
	exit;
});

$app->get('/admin/orders/:idorder', function($idorder){
	User::verifyLogin();
	$order = new Order();
	$order->get((int)$idorder);
	$cart = $order->getCart();
	$page = new PageAdmin();
	$page->setTpl('order',[
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);
});

$app->get('/admin/orders/:idorder/status', function($idorder){
	User::verifyLogin();
	$order = new Order();
	$order->get((int)$idorder);
	$page = new PageAdmin();
	$page->setTpl('order-status',[
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgError'=>Order::getError(),
		'msgSuccess'=>Order::getSuccess()
	]);
});

$app->post('/admin/orders/:idorer/status', function($idorder){
	User::verifyLogin();
	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0){
		Order::setError("Informe o status atual");
		header("Location: /admin/orders/".$idorder."/status");
		exit;
	}
	$order = new Order();
	$order->get((int)$idorder);
	$order->setidstatus((int)$_POST['idstatus']);
	$order->save();
	$order->setSuccess('Status atualizado.');
	header("Location: /admin/orders/".$idorder."/status");
	exit;
});
$app->run();

 ?>