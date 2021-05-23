<?php

use WHMCS\Database\Capsule;
use WHMCS\User\Alert;

// Leva o que preciso para dentro de funções
global $idusuario;
global $Arquivo;
global $Arquivo_Nome;
global $statusdocumentofrente;
global $statusdocumentoverso;
global $statuscomprovantedeendereco;
global $MotivoRegeicaoRGFrente;
global $MotivoRegeicaoRGVerso;
global $MotivoRegeicaoComprovante;

// Verifica dados do cliente

$idusuario = $_SESSION['uid'];

// Cria a Tabela do sistema
try {
	if (!WHMCS\Database\Capsule::schema()->hasTable('mmhospedagem_validacaodeconta')) {
		WHMCS\Database\Capsule::schema()->create('mmhospedagem_validacaodeconta', function ($table)
		{
			$table->increments('id');
			$table->string('idCliente');
			$table->string('ArquivoRGFrente');
			$table->string('ArquivoRGVerso');
			$table->string('ArquivoComrpovante');
			$table->integer('StatusRGFrente');
			$table->integer('StatusRGVerso');
			$table->integer('StatusComrpovante');
			$table->date('DatadoEnvioRGFrente');
			$table->date('DatadoEnvioRGVerso');
			$table->date('DatadoEnvioComprovante');
			$table->string('MotivoRegeicaoRGFrente');
			$table->string('MotivoRegeicaoRGVerso');
			$table->string('MotivoRegeicaoComprovante');
		});
	}
}
catch (Exception $e) {
	return array('status' => 'error', 'description' => 'Não foi possivel criar a tabela erro: ' . $e->getMessage());
}

// Adiciona os clientes a tabela do sistema
try {
	foreach (Capsule::table('mmhospedagem_validacaodeconta')->get() as $mmhospedagemvalidacaodeconta) {	
		$Validacao_idCliente = (int)$mmhospedagemvalidacaodeconta->idCliente;
	}
} catch (\Exception $e) {
	echo $e->getMessage();
}

// Verifica e adiciona a conta do usuario a tabela do sistema quando acessado uma vez!
if($Validacao_idCliente == $idusuario) { } else {
	//Inserindo dados no banco de dados
	Capsule::connection()->transaction(function ($connectionManager){

		global $idusuario;

        /** @var \Illuminate\Database\Connection $connectionManager */
        $connectionManager->table('mmhospedagem_validacaodeconta')->insert([
        	'idCliente' => $idusuario,
        	'StatusRGFrente' => '0', 
        	'StatusRGVerso' => '0', 
        	'StatusComrpovante' => '0',
        ]);
	});
}

try {
	foreach (Capsule::table('mmhospedagem_validacaodeconta')->WHERE('idCliente', $idusuario)->get() as $dadosdomodulovalidacaodeconta) {	
		$statusdocumentofrente = $dadosdomodulovalidacaodeconta->StatusRGFrente;
		$statusdocumentoverso = $dadosdomodulovalidacaodeconta->StatusRGVerso;
		$statuscomprovantedeendereco = $dadosdomodulovalidacaodeconta->StatusComrpovante;
		$MotivoRegeicaoRGFrente = $dadosdomodulovalidacaodeconta->MotivoRegeicaoRGFrente;
		$MotivoRegeicaoRGVerso = $dadosdomodulovalidacaodeconta->MotivoRegeicaoRGVerso;
		$MotivoRegeicaoComprovante = $dadosdomodulovalidacaodeconta->MotivoRegeicaoComprovante;
	}
} catch (\Exception $e) {
	echo $e->getMessage();
}






if( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' )
{  

	function getMimeTypes( array $extensions )
	{
	    // coloque o caminho para o local onde foi salvo o arquivo MimeTypes
	    $mimes = file_get_contents( 'Mime/MimeTypes', FILE_USE_INCLUDE_PATH );
	    $mime_types = array( );
	    
	    foreach( $extensions as $search )
	    {
	        preg_match_all( sprintf( '/^.%s[^\n\r]+/mi', $search ), $mimes, $matches );
	        $mime_types = array_merge( $mime_types, preg_replace( '/^[\S]+[[:space:]]+/', null, $matches[ 0 ] ) );
	    }
	    
	    return $mime_types;
	}

	if(isset($_FILES["Arquivo_nfe"])) {

		$Arquivo = array();

		foreach($_FILES["Arquivo_nfe"]["tmp_name"] as $key=>$tmp_name){
			$Arquivo = $_FILES["Arquivo_nfe"];
			$Pasta_Destino = "./includes/hooks/arquivos/";

			$extensao = strtolower(end(explode('.',$Arquivo["name"][$key])));

			$Arquivo_Nome = md5(time().$Arquivo["name"][$key]).'.'.$extensao;

			// Verifica a extensão dos arquivos permitidos
			$allowed =  array('jpeg', 'jpg', 'png', 'gif', 'pdf');
			$filename = $Arquivo["name"][$key];
			$ext = pathinfo($filename, PATHINFO_EXTENSION);

			$tamanho_max = 1024 * 1024 * 5;



			if($tamanho_max < $_FILES['documento']['size']){
    		  	header('Location: ./clientarea.php?statusdoenviodocumento=errotamanho');
				exit();
    		}

			if(in_array($ext,$allowed) ) {
			    $upload = move_uploaded_file($Arquivo["tmp_name"][$key], $Pasta_Destino . $Arquivo_Nome);
			}

			
		}

		// Tratamento dos arquivos
		$documentofrente = md5(time().$Arquivo["name"]["documento-frente"]).'.'.strtolower(end(explode('.', $Arquivo["name"]["documento-frente"])));
		$documentoverso = md5(time().$Arquivo["name"]["documento-verso"]).'.'.strtolower(end(explode('.', $Arquivo["name"]["documento-verso"])));
		$comprovante = md5(time().$Arquivo["name"]["endereco"]).'.'.strtolower(end(explode('.', $Arquivo["name"]["endereco"])));

		$dataatual = date('Y-m-d');

		if(in_array($ext,$allowed) ) {

			if($Arquivo["name"]["documento-frente"] > '') {

			 	try {
					$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.ArquivoRGFrente = '$documentofrente', mmhospedagem_validacaodeconta.StatusRGFrente = '1', mmhospedagem_validacaodeconta.DatadoEnvioRGFrente = '$dataatual' WHERE mmhospedagem_validacaodeconta.idCliente = $idusuario";
					// Executa a query
					$inserir = mysql_query($query);

					//Inserindo dados no banco de dados TODOLIST
					Capsule::connection()->transaction(
					    function ($connectionManager)
					    {
					        /** @var \Illuminate\Database\Connection $connectionManager */
					        $connectionManager->table('tbltodolist')->insert(['date' => ''.date('Y-m-d').'','title' => '[Validação de Conta] - RG Frente ','description' => 'O cliente ID <a href="./clientssummary.php?userid='.$_SESSION["uid"].'">#'.$_SESSION["uid"].'</a> esta aguardando validação da frente do RG, por favor verificar no perfil do cliente.','admin' => '0','status' => 'Pending','duedate' => ''.date('Y-m-d', strtotime('+3 days')).'',]);
					    }
					);



					$command = 'SendEmail';
					$postData = array(
						'messagename' => 'Client Signup Email',
						'id' => $idusuario,
						'customtype' => 'general',
						'customsubject' => 'Validação de Conta! [Frente RG]',
						'custommessage' => '
						
						Olá, tudo bem?
						Recebemos seus documentos! Pedimos que aguarde 24 horas úteis para que possamos analisar os arquivos enviados, e desta forma validar sua conta!

						Note que seus pedidos só seram liberados após a validação da sua conta!

						----
						Atenciosamente,
						Maik Venâncio de Oliveira
						Equipe: Suporte Técnico nível 5 / Gerente de contas
						Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
						
						',
					);

					$results = localAPI($command, $postData);



				}
				catch (\Exception $e){
                    //mensagem de erro
                    echo 'O erro esta aqui ArquivoRGFrente!';
                    exit();
                }
			}




			if($Arquivo["name"]["documento-verso"] > '') {
				try {
					$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.ArquivoRGVerso = '$documentoverso', mmhospedagem_validacaodeconta.StatusRGVerso = '1', mmhospedagem_validacaodeconta.DatadoEnvioRGVerso = '$dataatual'  WHERE mmhospedagem_validacaodeconta.idCliente = $idusuario";
					// Executa a query
					$inserir = mysql_query($query);

					//Inserindo dados no banco de dados TODOLIST
					Capsule::connection()->transaction(
					    function ($connectionManager)
					    {
					        /** @var \Illuminate\Database\Connection $connectionManager */
					        $connectionManager->table('tbltodolist')->insert(['date' => ''.date('Y-m-d').'','title' => '[Validação de Conta] - RG Verso ','description' => 'O cliente ID <a href="./clientssummary.php?userid='.$_SESSION["uid"].'">#'.$_SESSION["uid"].'</a> esta aguardando validação do verso do RG, por favor verificar no perfil do cliente.','admin' => '0','status' => 'Pending','duedate' => ''.date('Y-m-d', strtotime('+3 days')).'',]);
					    }
					);

					$command = 'SendEmail';
					$postData = array(
						'messagename' => 'Client Signup Email',
						'id' => $idusuario,
						'customtype' => 'general',
						'customsubject' => 'Validação de Conta! [Verso RG]',
						'custommessage' => '
						
						Olá, tudo bem?
						Recebemos seus documentos! Pedimos que aguarde 24 horas úteis para que possamos analisar os arquivos enviados, e desta forma validar sua conta!

						Note que seus pedidos só seram liberados após a validação da sua conta!

						----
						Atenciosamente,
						Maik Venâncio de Oliveira
						Equipe: Suporte Técnico nível 5 / Gerente de contas
						Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
						
						',
					);

					$results = localAPI($command, $postData);


				}
				catch (\Exception $e){
                    //mensagem de erro
                    echo 'O erro esta aqui ArquivoRGVerso!';
                    exit();
                }
			}

			if($Arquivo["name"]["endereco"] > '') {
				try {
					$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.ArquivoComrpovante = '$comprovante', mmhospedagem_validacaodeconta.StatusComrpovante = '1', mmhospedagem_validacaodeconta.DatadoEnvioComprovante = '$dataatual' WHERE mmhospedagem_validacaodeconta.idCliente = $idusuario";
					// Executa a query
					$inserir = mysql_query($query);

					//Inserindo dados no banco de dados TODOLIST
					Capsule::connection()->transaction(
					    function ($connectionManager)
					    {
					        /** @var \Illuminate\Database\Connection $connectionManager */
					        $connectionManager->table('tbltodolist')->insert(['date' => ''.date('Y-m-d').'','title' => '[Validação de Conta] - Comprovante ','description' => 'O cliente ID <a href="./clientssummary.php?userid='.$_SESSION["uid"].'">#'.$_SESSION["uid"].'</a> esta aguardando validação do Comprovante de Endereço, por favor verificar no perfil do cliente.','admin' => '0','status' => 'Pending','duedate' => ''.date('Y-m-d', strtotime('+3 days')).'',]);
					    }
					);


					$command = 'SendEmail';
					$postData = array(
						'messagename' => 'Client Signup Email',
						'id' => $idusuario,
						'customtype' => 'general',
						'customsubject' => 'Validação de Conta! [Comprovante de Endereço]',
						'custommessage' => '
						
						Olá, tudo bem?
						Recebemos seu comprovante de residencia! Pedimos que aguarde 24 horas úteis para que possamos analisar os arquivos enviados, e desta forma validar sua conta!

						Note que seus pedidos só seram liberados após a validação da sua conta!

						----
						Atenciosamente,
						Maik Venâncio de Oliveira
						Equipe: Suporte Técnico nível 5 / Gerente de contas
						Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
						
						',
					);

					$results = localAPI($command, $postData);
				}
				catch (\Exception $e){
                    //mensagem de erro
                    echo 'O erro esta aqui ArquivoComrpovante!';
                    exit();
                }
			}

			

			header('Location: ./clientarea.php?statusdoenviodocumento=sucesso');
			exit();
		} else {
			header('Location: ./clientarea.php?statusdoenviodocumento=erro');
			exit();
		}
	}
}


//Alertas no topo
if($statusdocumentofrente == '0' || $statusdocumentoverso == '0' || $statuscomprovantedeendereco == '0'){
	add_hook('ClientAlert', 1, function($client) {
	    $firstName = $client->firstName;
	    $lastName = $client->lastName;
	    return new Alert(
	        "Olá {$firstName} Tudo bem? Você precisa validar sua conta!",
	        'warning' //see http://getbootstrap.com/components/#alerts
	    );
	});
}


if($statusdocumentofrente == '1' || $statusdocumentoverso == '1' || $statuscomprovantedeendereco == '1'){
	add_hook('ClientAlert', 1, function($client) {
	    $firstName = $client->firstName;
	    $lastName = $client->lastName;
	    return new Alert(
	        "Olá {$firstName} Tudo bem? Os documentos que você nós enviou estão em analise porfavor aguarde o setor de validação revisar seus dados! Assim que sua conta for validada você sera avisado!",
	        'info' //see http://getbootstrap.com/components/#alerts
	    );
	});
}


if($statusdocumentofrente == '3' || $statusdocumentoverso == '3' || $statuscomprovantedeendereco == '3'){
	add_hook('ClientAlert', 1, function($client) {
	    $firstName = $client->firstName;
	    $lastName = $client->lastName;
	    return new Alert(
	        "Op's um dos documentos foi rejeitado pelo nosso setor de validação! Pedimos que por favor envie o documento novamente!<br />
	        <strong>AVISO:</strong> Note que a não validação de sua conta resultara em suspensão dos seus serviços!",
	        'danger' //see http://getbootstrap.com/components/#alerts
	    );
	});
}




// Hook template cliente
add_hook('ClientAreaHomepagePanels', 1, function($homePagePanels) {

	global $idusuario;
	global $statusdocumentofrente;
	global $statusdocumentoverso;
	global $statuscomprovantedeendereco;
	global $MotivoRegeicaoRGFrente;
	global $MotivoRegeicaoRGVerso;
	global $MotivoRegeicaoComprovante;

	    $newPanel = $homePagePanels->addChild(
	        'unique-css-name',
	        array(
	            'name' => 'Documentos',
	            'label' => 'Validação de Conta',
	            'icon' => 'fa-lock', //see http://fortawesome.github.io/Font-Awesome/icons/
	            'order' => '1',
	            'extras' => array(
	                'color' => 'blue', //see Panel Accents in template styles.css
	               
	            ),
	        )
	    );


    	$statusenviodc = $_GET['statusdoenviodocumento'];

	    $conteudo = '<link href="./includes/hooks/mmhospedagem_enviodocumentos/estilos.css" rel="stylesheet">

	    <form name="Form_Upload_Arquivo" method="post" enctype="multipart/form-data">';

	    
	    $templatealerta = ' 
	    <div class="alert alert-warning" role="alert">
		    <p style="padding: 13px 13px 5px; font-size: 11px;">
			<i class="fa fa-info-circle fa-5" aria-hidden="true"></i>
    		<strong>AVISO:</strong><br />
		    A MMHospedagem leva a sério proteção contra fraudes, segurança e abuso de identidade. Como tal, temos muitas práticas e políticas em vigor para detectar pedidos fraudulentos. Uma dessas políticas exige a verificação de Identidade. Para agilizar e garantir que seu processo de verificação seja concluído sem problemas, envie os documentos a seguir.</p>
		</div>';
		

	    // Tratamento STATUS
	    if($statusenviodc == 'erro') {
	    	$conteudo .= '
	    	<div class="alert-message error-status-mm-documento">
		    	<h4><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> <strong>Oops! Algo deu errado!</strong><br> Só e permitido as extensões PNG e JPG!</h4>
		    </div>
	    ';
	    }

	    if($statusenviodc == 'sucesso') {
	    	$conteudo .= '
	    	<div class="alert-message sucesso-status-mm-documento">
		    	<h4><i class="fa fa-check" aria-hidden="true"></i> <strong>Sucesso!</strong><br> Recebemos seus documentos com sucesso!</h4>
		    </div>
	    ';
	    }

	    if($statusdocumentoverso == '2' & $statusdocumentoverso == '2' & $statuscomprovantedeendereco == '2') {
	    	$conteudo .= '
	    	<div class="alert-message sucesso-status-mm-documento">
		    	<h4><i class="fa fa-check" aria-hidden="true"></i> <strong>Sucesso!</strong><br> Sua conta foi validada com sucesso!</h4>
		    </div>
	    ';
	    }



	    // Template documento negado

	    if($statusdocumentofrente == '3'){
	    	$conteudo .= '
	    	
	    	<div class="alert-message error-status-mm-documento">
		    	<h4>
			    	<i class="fa fa-closed" aria-hidden="true"></i> 
			    	<strong>Frente do RG</strong>
			    	<br /> 
			    	Não foi possivel validar seu documento!<br />
			    	Motivo: 
			    	<div style="background-color: #FF4D4D; padding: 8px 10px; border-radius: 5px; margin-top: 7px; margin-right: 18px;">
			    		'.$MotivoRegeicaoRGFrente.'
			    	</div>
		    	</h4>
		    </div>

	    	';
	    }


	    if($statusdocumentoverso == '3'){
	    	$conteudo .= '
	    	
	    	<div class="alert-message error-status-mm-documento">
		    	<h4>
			    	<i class="fa fa-closed" aria-hidden="true"></i> 
			    	<strong>Verso do RG</strong>
			    	<br /> 
			    	Não foi possivel validar seu documento!<br />
			    	Motivo: 
			    	<div style="background-color: #FF4D4D; padding: 8px 10px; border-radius: 5px; margin-top: 7px; margin-right: 18px;">
			    		'.$MotivoRegeicaoRGVerso.'
			    	</div>
		    	</h4>
		    </div>

	    	';
	    }


	    if($statuscomprovantedeendereco == '3'){
	    	$conteudo .= '
	    	
	    	<div class="alert-message error-status-mm-documento">
		    	<h4>
			    	<i class="fa fa-closed" aria-hidden="true"></i> 
			    	<strong>Comprovante de Endereço</strong>
			    	<br /> 
			    	Não foi possivel validar seu documento!<br />
			    	Motivo: 
			    	<div style="background-color: #FF4D4D; padding: 8px 10px; border-radius: 5px; margin-top: 7px; margin-right: 18px;">
			    		'.$MotivoRegeicaoComprovante.'
			    	</div>
		    	</h4>
		    </div>

	    	';
	    }


	    // Template documento em analise

	    if ($statusdocumentofrente == '1'){
	    	$conteudo .= '
	    	
	    	<div class="alert-message emanalise-status-mm-documento">
		    	<h4>
			    	<i class="fa fa-clock-o" aria-hidden="true"></i> 
			    	<strong>Frente do RG</strong>
			    	<br /> 
			    	O documento enviado sera analisado em ate 24 horas úteis!
		    	</h4>
		    </div>

	    	';
	    }

	    if ($statusdocumentoverso == '1'){
	    	$conteudo .= '
	    	
	    	<div class="alert-message emanalise-status-mm-documento">
		    	<h4>
			    	<i class="fa fa-clock-o" aria-hidden="true"></i> 
			    	<strong>Verso do RG</strong>
			    	<br /> 
			    	O documento enviado sera analisado em ate 24 horas úteis!
		    	</h4>
		    </div>

	    	';
	    }

	    if ($statuscomprovantedeendereco == '1'){
	    	$conteudo .= '
	    	
	    	<div class="alert-message emanalise-status-mm-documento">
		    	<h4>
			    	<i class="fa fa-clock-o" aria-hidden="true"></i> 
			    	<strong>Comprovante de Endereço</strong>
			    	<br /> 
			    	O documento enviado sera analisado em ate 24 horas úteis!
		    	</h4>
		    </div>

	    	';
	    }


    	// Templates Geral

    	$templatergfrente .= '<div class="caixa-documentos">
	    	<div class="envio-de-documentos">
		    	<label for="Arquivo_nfe[documento-frente]" class="frente-rg" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Procurar arquivo" aria-describedby="tooltip357387"></label>
		    	<input id="Arquivo_nfe[documento-frente]" onchange="this.form.frenterg.value = this.value;" type="file" class="form-control" name="Arquivo_nfe[documento-frente]" />
	    	</div>

	    	<div class="inputs">
		    	<p><i class="fa fa-folder-open-o"></i> Anexar Frente do RG</p>
		    	<input id="frenterg" name="frenterg" class="form-control"  type="text" readonly>
	    	</div>

    		<div class="clear"></div>
    	</div>';
   


    	$templatergverso .= '<div class="caixa-documentos">
	    	<div class="envio-de-documentos">
		    	<label for="Arquivo_nfe[documento-verso]" class="verso-rg" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Procurar arquivo" aria-describedby="tooltip357387"></label>
		    	<input id="Arquivo_nfe[documento-verso]" onchange="this.form.versorg.value = this.value;" type="file" class="form-control" name="Arquivo_nfe[documento-verso]" />
	    	</div>

	    	<div class="inputs">
		    	<p><i class="fa fa-folder-open-o"></i> Anexar Verso do RG</p>
		    	<input id="versorg" name="versorg" class="form-control"  type="text" readonly>
	    	</div>

    		<div class="clear"></div>
    	</div>';
 
    	


    	$templatecomprovante .= '<div class="caixa-documentos">
	    	<div class="envio-de-documentos">
		    	<label for="Arquivo_nfe[endereco]" class="comprovante" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Procurar arquivo" aria-describedby="tooltip357387"></label>
		    	<input id="Arquivo_nfe[endereco]" onchange="this.form.comprovantedeendereco.value = this.value;" type="file" class="form-control" name="Arquivo_nfe[endereco]" />
	    	</div>

	    	<div class="inputs">
		    	<p><i class="fa fa-folder-open-o"></i> Anexar Comprovante de Endereço</p>
		    	<input id="comprovantedeendereco" name="comprovantedeendereco" class="form-control"  type="text" readonly>
	    	</div>

    		<div class="clear"></div>
    	</div>';


    	$templatebotao .= '<button style="width: 100%;" class="btn btn-success" type="submit"><i class="fa fa-send-o"></i> Enviar documento</button>';
    	
    	$templaterodape .= '</form>';




		// Repeat as needed to add enough children
	    $newPanel->addChild(
	        'unique-css-name-id0',
	        array(
	            'label' => $conteudo,
	            'order' => 10,
	        )
	    );

	    if($statusdocumentofrente == '0' || $statusdocumentoverso == '0' || $statuscomprovantedeendereco == '0') {
		    // Repeat as needed to add enough children
		    $newPanel->addChild(
		        'unique-css-name-id1',
		        array(
		            'label' => $templatealerta,
		            'order' => 11,
		        )
		    );
		}

	    if($statusdocumentofrente == '0' || $statusdocumentofrente == '3') {
		    // Repeat as needed to add enough children
		    $newPanel->addChild(
		        'unique-css-name-id2',
		        array(
		            'label' => $templatergfrente ,
		            'order' => 12
		        )
		    );
		}

		if($statusdocumentoverso == '0' || $statusdocumentoverso == '3') {
		
		    // Repeat as needed to add enough children
		    $newPanel->addChild(
		        'unique-css-name-id3',
		        array(
		            'label' => $templatergverso,
		            'order' => 13,
		        )
		    );
		}
	   
	   	if($statuscomprovantedeendereco == '0' || $statuscomprovantedeendereco == '3') {
	   		// Repeat as needed to add enough children
		    $newPanel->addChild(
		        'unique-css-name-id4',
		        array(
		            'label' => $templatecomprovante,
		            'order' => 14,
		        )
		    );
	   	}
		
	   	if($statusdocumentofrente == '0' || $statusdocumentoverso == '0' || $statuscomprovantedeendereco == '0' || $statusdocumentofrente == '3' || $statusdocumentoverso == '3' || $statuscomprovantedeendereco == '3') {
		    // Repeat as needed to add enough children
		    $newPanel->addChild(
		        'unique-css-name-id5',
		        array(
		            'label' => $templatebotao,
		            'order' => 15,
		        )
		    );
		}

	    // Repeat as needed to add enough children
	    $newPanel->addChild(
	        'unique-css-name-id6',
	        array(
	            'label' => $templaterodape,
	            'order' => 16,
	        )
	    );

});

// Templates e sistemas da área administrativa

add_hook('AdminAreaClientSummaryPage', 1, function($vars) {


	$id_user = $_GET['userid'];
    //contando resultados dos documentos se existem


	global $idusuario;
	global $Arquivo;
	global $Arquivo_Nome;
	global $statusdocumentofrente;
	global $statusdocumentoverso;
	global $statuscomprovantedeendereco;

    $totaldocumentorgfrente = Capsule::table('mmhospedagem_validacaodeconta')->WHERE('idCliente', $id_user)->WHERE('StatusRGFrente', '0')->count();
    $totaldocumentorgverso = Capsule::table('mmhospedagem_validacaodeconta')->WHERE('idCliente', $id_user)->WHERE('StatusRGVerso', '0')->count();
    $totalcomprovanteresidencia = Capsule::table('mmhospedagem_validacaodeconta')->WHERE('idCliente', $id_user)->WHERE('StatusComrpovante', '1')->count();


	try {
	foreach (Capsule::table('mmhospedagem_validacaodeconta')->WHERE('idCliente', $id_user)->get() as $dadosdomodulovalidacaodeconta) {	
		$statusdocumentofrente = $dadosdomodulovalidacaodeconta->StatusRGFrente;
		$statusdocumentoverso = $dadosdomodulovalidacaodeconta->StatusRGVerso;
		$statuscomprovantedeendereco = $dadosdomodulovalidacaodeconta->StatusComrpovante;

		$documentorgfrenteimagem = $dadosdomodulovalidacaodeconta->ArquivoRGFrente;
		$documentorgversoimagem = $dadosdomodulovalidacaodeconta->ArquivoRGVerso;
		$documentorcomprovanteimagem = $dadosdomodulovalidacaodeconta->ArquivoComrpovante;
	}
	} catch (\Exception $e) {
		echo $e->getMessage();
	}


	// Download dos arquivos
	if($_GET['mm'] == 'downloadarquivofrente') {
		$arquivo = '../includes/hooks/arquivos/'.$documentorgfrenteimagem;
	    if(file_exists($arquivo)) {
	        $arquivo_nome = basename($arquivo);
	        $arquivo_size = filesize($arquivo);

	        //Output header
	        header("Cache-Control: private");
	        header("Content-Type: application/stream");
	        header("Content-Length: ".$arquivo_size);
	        header("Content-Disposition: attachment; filename=".$arquivo_nome);

	        //Saida do Arquivo.
	        readfile ($arquivo);                   
	        exit();
	    }
	    else {
	    	echo $arquivo;
	        die('Arquivo inválido.');

	        

	    }
	}


	if($_GET['mm'] == 'downloadarquivoverso') {
		$arquivo = '../includes/hooks/arquivos/'.$documentorgversoimagem;
	    if(file_exists($arquivo)) {
	        $arquivo_nome = basename($arquivo);
	        $arquivo_size = filesize($arquivo);

	        //Output header
	        header("Cache-Control: private");
	        header("Content-Type: application/stream");
	        header("Content-Length: ".$arquivo_size);
	        header("Content-Disposition: attachment; filename=".$arquivo_nome);

	        //Saida do Arquivo.
	        readfile ($arquivo);                   
	        exit();
	    }
	    else {
	        die('Arquivo inválido.');
	    }
	}


	if($_GET['mm'] == 'downloadarquivocomprovante') {
		$arquivo = '../includes/hooks/arquivos/'.$documentorcomprovanteimagem;
	    if(file_exists($arquivo)) {
	        $arquivo_nome = basename($arquivo);
	        $arquivo_size = filesize($arquivo);

	        //Output header
	        header("Cache-Control: private");
	        header("Content-Type: application/stream");
	        header("Content-Length: ".$arquivo_size);
	        header("Content-Disposition: attachment; filename=".$arquivo_nome);

	        //Saida do Arquivo.
	        readfile ($arquivo);                   
	        exit();
	    }
	    else {
	        die('Arquivo inválido.');
	    }
	}

	// Altera os status
	if($_GET['mm'] == 'aprovarrgfrente') {
		$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.StatusRGFrente = '2' WHERE mmhospedagem_validacaodeconta.idCliente = $id_user";
		// Executa a query
		$inserir = mysql_query($query);


		$command = 'SendEmail';
		$postData = array(
			'messagename' => 'Client Signup Email',
			'id' => $id_user,
			'customtype' => 'general',
			'customsubject' => 'Documento Aprovado! [RG Frente]',
			'custommessage' => '
			
			Olá, tudo bem?
			O documento RG Frente foi validado com sucesso! Lembre-se de manter seu dados sempre atualizados! 

			----
			Atenciosamente,
			Maik Venâncio de Oliveira
			Equipe: Suporte Técnico nível 5 / Gerente de contas
			Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
			
			',
		);

		$results = localAPI($command, $postData);

		header('Location: ./clientssummary.php?userid='.$id_user);
		exit();
	}

	if($_GET['mm'] == 'aprovarrgverso') {
		$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.StatusRGVerso = '2' WHERE mmhospedagem_validacaodeconta.idCliente = $id_user";
		// Executa a query
		$inserir = mysql_query($query);

		$command = 'SendEmail';
		$postData = array(
			'messagename' => 'Client Signup Email',
			'id' => $id_user,
			'customtype' => 'general',
			'customsubject' => 'Documento Aprovado! [RG Verso]',
			'custommessage' => '
			
			Olá, tudo bem?
			O documento RG Verso foi validado com sucesso! Lembre-se de manter seu dados sempre atualizados! 

			----
			Atenciosamente,
			Maik Venâncio de Oliveira
			Equipe: Suporte Técnico nível 5 / Gerente de contas
			Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
			
			',
		);

		$results = localAPI($command, $postData);

		header('Location: ./clientssummary.php?userid='.$id_user);
		exit();
	}

	if($_GET['mm'] == 'aprovarcomprovante') {
		$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.StatusComrpovante = '2' WHERE mmhospedagem_validacaodeconta.idCliente = $id_user";
		// Executa a query
		$inserir = mysql_query($query);

		$command = 'SendEmail';
		$postData = array(
			'messagename' => 'Client Signup Email',
			'id' => $id_user,
			'customtype' => 'general',
			'customsubject' => 'Documento Aprovado! [Comprovante de Endereço]',
			'custommessage' => '
			
			Olá, tudo bem?
			O comprovante de endereço foi validado com sucesso! Lembre-se de manter seu dados sempre atualizados! 

			----
			Atenciosamente,
			Maik Venâncio de Oliveira
			Equipe: Suporte Técnico nível 5 / Gerente de contas
			Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
			
			',
		);

		$results = localAPI($command, $postData);

		header('Location: ./clientssummary.php?userid='.$id_user);
		exit();
	}

	$motivoregeicaorgfrentestring = $_POST['motivoreprovacaorgfrente'];
	if($_POST['motivoreprovacaorgfrente']) {
		$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.StatusRGFrente = '3', mmhospedagem_validacaodeconta.MotivoRegeicaoRGFrente = '$motivoregeicaorgfrentestring' WHERE mmhospedagem_validacaodeconta.idCliente = $id_user";
		// Executa a query
		$inserir = mysql_query($query);


		$command = 'SendEmail';
		$postData = array(
			'messagename' => 'Client Signup Email',
			'id' => $id_user,
			'customtype' => 'general',
			'customsubject' => 'Documento Reprovado! [RG Frente]',
			'custommessage' => '
			
			Olá, tudo bem?
			A documentação enviada não foi aprovada possivelmente por não estar visivel ou os dados do seu documento estão divergentes dos dados que foram informados no cadastro! Pedimos que nos envie novamente a documentação para que possamos analisar o mesmo novamente!

			----
			Atenciosamente,
			Maik Venâncio de Oliveira
			Equipe: Suporte Técnico nível 5 / Gerente de contas
			Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
			
			',
		);

		$results = localAPI($command, $postData);


		header('Location: ./clientssummary.php?userid='.$id_user);
		exit();
	}

	$motivoregeicaorgversostring = $_POST['motivoreprovacaorgverso'];
	if($_POST['motivoreprovacaorgverso']) {
		$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.StatusRGVerso = '3', mmhospedagem_validacaodeconta.MotivoRegeicaoRGVerso = '$motivoregeicaorgversostring' WHERE mmhospedagem_validacaodeconta.idCliente = $id_user";
		// Executa a query
		$inserir = mysql_query($query);

		$command = 'SendEmail';
		$postData = array(
			'messagename' => 'Client Signup Email',
			'id' => $id_user,
			'customtype' => 'general',
			'customsubject' => 'Documento Reprovado! [RG Verso]',
			'custommessage' => '
			
			Olá, tudo bem?
			A documentação enviada não foi aprovada possivelmente por não estar visivel ou os dados do seu documento estão divergentes dos dados que foram informados no cadastro! Pedimos que nos envie novamente a documentação para que possamos analisar o mesmo novamente!

			----
			Atenciosamente,
			Maik Venâncio de Oliveira
			Equipe: Suporte Técnico nível 5 / Gerente de contas
			Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
			
			',
		);

		$results = localAPI($command, $postData);

		header('Location: ./clientssummary.php?userid='.$id_user);
		exit();
	}

	$motivoregeicaocomprovantestring = $_POST['motivoreprovacaocomprovante'];
	if($_POST['motivoreprovacaocomprovante']) {
		$query = "UPDATE mmhospedagem_validacaodeconta SET mmhospedagem_validacaodeconta.StatusComrpovante = '3', mmhospedagem_validacaodeconta.MotivoRegeicaoComprovante = '$motivoregeicaocomprovantestring' WHERE mmhospedagem_validacaodeconta.idCliente = $id_user";
		// Executa a query
		$inserir = mysql_query($query);


		$command = 'SendEmail';
		$postData = array(
			'messagename' => 'Client Signup Email',
			'id' => $id_user,
			'customtype' => 'general',
			'customsubject' => 'Documento Reprovado! [Comprovante de Endereço]',
			'custommessage' => '
			
			Olá, tudo bem?
			O seu comprovante de endereço não foi aprovado possivelmente por não estar visivel ou os dados do seu comprovante de residencia estão divergentes dos dados que foram informados no cadastro! Pedimos que nos envie novamente o comprovante de endereço para que possamos analisar o mesmo novamente!

			----
			Atenciosamente,
			Maik Venâncio de Oliveira
			Equipe: Suporte Técnico nível 5 / Gerente de contas
			Tel: (062) 4101-9380 / (062) 3637-8943 / (062) 9 8134-1442
			
			',
		);

		$results = localAPI($command, $postData);

		header('Location: ./clientssummary.php?userid='.$id_user);
		exit();
	}

    $templateadmin = '

    	<div class="panel panel-default">
 			<div class="panel-heading">
				<i class="fa fa-user" aria-hidden="true"></i> Validação de Conta			
			</div>
  			
			<div class="panel-body">
    			<div class="row" style="margin: 0px -15px; width: 103%;">
    				<div class="col-md-8" style="width: 100%;">
						
					<table class="table table-hover table-bordered results" style="border-radius: 5px;">
					  <tbody><tr style="background-color: rgba(0,0,0,.05);">
						<td style="text-align: center;">Documento RG Frente</td>
						<td style="text-align: center;">Documento RG Verso</td>
						<td style="text-align: center;">Comprovante de Endereço</td>
					  </tr>
					</tbody><tbody>
						<tr>
							<td style="text-align: center;">';

	if($statusdocumentofrente == '0') {
		$templateadmin .= '<span class="btn btn-info" style="width: 100%; cursor: auto; padding: 5px 13px;">Não foi enviado arquivo!</span>';
	}

	if($statusdocumentofrente == '1') {

		$templateadmin .= '

		<span class="btn btn-warning" style="cursor: auto;">Aguardando analise deste documento!</span>
		<div class="clear"></div>

		<div class="btn-group"> 
			<a style="margin-top: 7px; padding: 5px 32px;" href="clientssummary.php?userid='.$id_user.'&mm=downloadarquivofrente" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Baixar arquivo</a>
		</div>
		
		<div class="btn-group"> 
			<a style="margin-top: 7px; padding: 5px 26px;" href="clientssummary.php?userid='.$id_user.'&mm=aprovarrgfrente" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Aprovar</a>
		</div>
		
		<form action="clientssummary.php?userid='.$id_user.'&mm=reprovarrgfrente" method="POST">
			<div class="btn-group"> 
				<input type="text" value="" class="btn btn-danger btn-sm" style="width: 194px; margin-top: 7px; color: #333; cursor: auto; background-color: #FFF;" placeholder="Informe o motivo" name="motivoreprovacaorgfrente" id="motivoreprovacaorgfrente" required>
				<button style="margin-top: 7px; cursor: auto;" class="btn btn-danger btn-sm"><i class="fa fa-close"></i> Reprovar</button>
			</div>	
		</form>


		';
	}

	if($statusdocumentofrente == '2') {
		$templateadmin .= '<span class="btn btn-success" style="width: 100%; cursor: auto; padding: 5px 13px;">Aprovado!</span>';
	}

	if($statusdocumentofrente == '3') {
		$templateadmin .= '<span class="btn btn-info" style="width: 100%; cursor: auto; padding: 5px 13px;">Não foi enviado arquivo!</span>
		<span class="btn btn-warning" style="cursor: auto; cursor: auto; width: 100%; margin-top: 5px;">Documento foi Reprovado!</span>';
	}

	$templateadmin .= '</td>
							<td style="text-align: center;">

							';

	if($statusdocumentoverso == '0') {
		$templateadmin .= '<span class="btn btn-info" style="width: 100%; cursor: auto; padding: 5px 13px;">Não foi enviado arquivo!</span>';
	}

	if($statusdocumentoverso == '1') {

		$templateadmin .= '

		<span class="btn btn-warning" style="cursor: auto;">Aguardando analise deste documento!</span>
		<div class="clear"></div>

		<div class="btn-group"> 
			<a style="margin-top: 7px; padding: 5px 32px;" href="clientssummary.php?userid='.$id_user.'&mm=downloadarquivoverso" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Baixar arquivo</a>
		</div>
		
		<div class="btn-group"> 
			<a style="margin-top: 7px; padding: 5px 26px;" href="clientssummary.php?userid='.$id_user.'&mm=aprovarrgverso" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Aprovar</a>
		</div>
		
		<form action="clientssummary.php?userid='.$id_user.'&mm=reprovarrgverso" method="POST">
			<div class="btn-group"> 
				<input type="text" value="" class="btn btn-danger btn-sm" style="width: 194px; margin-top: 7px; color: #333; cursor: auto; background-color: #FFF;" placeholder="Informe o motivo" name="motivoreprovacaorgverso" id="motivoreprovacaorgverso" required>
				<button style="margin-top: 7px; cursor: auto;" class="btn btn-danger btn-sm"><i class="fa fa-close"></i> Reprovar</button>
			</div>	
		</form>


		';
	}

	if($statusdocumentoverso == '2') {
		$templateadmin .= '<span class="btn btn-success" style="width: 100%; cursor: auto; padding: 5px 13px;">Aprovado!</span>';
	}

	if($statusdocumentoverso == '3') {
		$templateadmin .= '<span class="btn btn-info" style="width: 100%; cursor: auto; padding: 5px 13px;">Não foi enviado arquivo!</span>
		<span class="btn btn-warning" style="cursor: auto; cursor: auto; width: 100%; margin-top: 5px;">Documento foi Reprovado!</span>';
	}

	$templateadmin .= '

							</td>
							<td style="text-align: center;">';

	if($statuscomprovantedeendereco == '0') {
		$templateadmin .= '<span class="btn btn-info" style="width: 100%; cursor: auto; padding: 5px 13px;">Não foi enviado arquivo!</span>';
	}

	if($statuscomprovantedeendereco == '1') {

		$templateadmin .= '

		<span class="btn btn-warning" style="cursor: auto;">Aguardando analise deste documento!</span>
		<div class="clear"></div>

		<div class="btn-group"> 
			<a style="margin-top: 7px; padding: 5px 32px;" href="clientssummary.php?userid='.$id_user.'&mm=downloadarquivocomprovante" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Baixar arquivo</a>
		</div>
		
		<div class="btn-group"> 
			<a style="margin-top: 7px; padding: 5px 26px;" href="clientssummary.php?userid='.$id_user.'&mm=aprovarcomprovante" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Aprovar</a>
		</div>
		
		<form action="clientssummary.php?userid='.$id_user.'&mm=reprovarcomprovante" method="POST">
			<div class="btn-group"> 
				<input type="text" value="" class="btn btn-danger btn-sm" style="width: 194px; margin-top: 7px; color: #333; cursor: auto; background-color: #FFF;" placeholder="Informe o motivo" name="motivoreprovacaocomprovante" id="motivoreprovacaocomprovante" required>
				<button style="margin-top: 7px; cursor: auto;" class="btn btn-danger btn-sm"><i class="fa fa-close"></i> Reprovar</button>
			</div>	
		</form>


		';
	}

	if($statuscomprovantedeendereco == '2') {
		$templateadmin .= '<span class="btn btn-success" style="width: 100%; cursor: auto; padding: 5px 13px;">Aprovado!</span>';
	}

	if($statuscomprovantedeendereco == '3') {
		$templateadmin .= '<span class="btn btn-info" style="width: 100%; cursor: auto; padding: 5px 13px;">Não foi enviado arquivo!</span>
		<span class="btn btn-warning" style="cursor: auto; cursor: auto; width: 100%; margin-top: 5px;">Documento foi Reprovado!</span>';
	}

	$templateadmin .= '</td>
						</tr>
					</tbody>
					</table></div>
				</div>
			</div>
			
		</div>


    ';


    return $templateadmin;


});




?>