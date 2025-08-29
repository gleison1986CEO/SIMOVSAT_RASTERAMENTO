
<div class="container">
        <div class="row">
            <div id="card_edit_form">

                

                    <div class="panel-body">
                       <p id="titulo">OH! NÃO!</p>
                       <p id="error">A informação já está registrada em nosso banco de dados.</p>
                        <center><img id="img_"src="https://cdn.dribbble.com/users/24711/screenshots/3886002/falcon_persistent_connection_2x.gif" /></center>
                        <p id="error">porfavor revise seu arquivo csv e tente novamente. </p>
                        <p id="error">*revise se há algum dado duplicado em seu csv, ou cadastrado no banco de dados.</p>
                    </div>
                    
                </div>
                <p id="redirect">aguarde você será redirecionado ...</p>
            
        </div>
    </div>


  <script>
   setTimeout(function () {
   window.location.href = "chip"; 
}, 9000);
  </script>
<style>
  #redirect{
    color:#ffffff;
    font-weight:bold;
    font-size:29px;
    margin-top:5px;
    text-align:center;
    font-family: Arial, Helvetica, sans-serif;
  }
  #titulo{
    color:#00004d;
    font-weight:bold;
    font-size:43px;
    padding:2px;
    text-align:center;
    font-family: Arial, Helvetica, sans-serif;
  }
  #error{
    color:#00004d;
    font-weight:bold;
    font-size:16px;
    padding:2px;
    text-align:center;
    font-family: Arial, Helvetica, sans-serif;
  }
  body{
    background-color:#4700b3;
  }
    #sucesso{
        text-align: center;
        font-weight: bold;
        font-size: 30px;
        color:#ffcc00;
    }
    #campos{
      border-radius: 5px;
      margin-right: 6px;
      width: 100%;
      max-width: 190px;
      border:none;
      padding:10px;
      color:#000000;
      background-color: #ffcc00 !important;
    }
    .upload_button{
    text-align: center;
    display: inline-block;
    padding: 6px 12px;
    background-color: #f2f2f2;
    padding:15px;
    cursor: pointer;
    width: 100%;
    max-width: 400px;
    border: none;
    border-radius: 12px;
}
    input[type="file"] {
    display: none;
}
    #img_{
      width: 100%;
      max-width: 300px;
      padding:40px;
      margin-bottom: 30px;
      margin: 0 auto;
    }
    input[type=number]::-webkit-inner-spin-button { 
     -webkit-appearance: none;

    }
  input[type=number] { 
   -moz-appearance: textfield;
   appearance: textfield;

  }

    #input{
      padding:10px;
      margin-top:5px !important;
     
      margin-bottom: 5px !important;
    }
    .input_{
      width: 100%;
      background-color: #f2f2f2;
      font-weight: bold;
      padding:20px;
      border-radius: 12px;
      border:none;
    }
    #card_edit_form{
     padding:20px;
     max-width: 500px;
     width: 100%;
     margin:  0 auto;
     height: 600px;
     margin-top: 50px;
     align-items: center ;
     background-color: #ffffff;
     margin-bottom: 90px;
     border-radius: 12px;
     box-shadow: rgba(100, 100, 111, 0.2) 0px 7px 29px 0px;

    }
    .rastreador_button{
      border-radius: 5px;
      margin-right: 6px;
      width: 100%;
      max-width: 600px;
      align-content: center;
      border:none;
      padding:10px;
      color:#000000;
      background-color: #ffcc00 !important;
    }
    #rastreador_button{
      border-radius: 5px;
      margin-right: 6px;
      width: 100%;
      max-width: 600px;
      border:none;
      padding:10px;
      color:#000000;
      background-color: #ffcc00 !important;
    }
    #excluir_{
      border-radius: 5px;
      margin-right: 6px;
      border:none;
      padding:10px;
      color:#ffffff;
      background-color: #ff3300 !important;
    }
    td{
      padding:19px;
      text-align: center;
    }
  </style>