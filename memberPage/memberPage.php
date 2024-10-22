<?php
// Qui ci va ipoteticamente la roba del backend che devo aspoettare che esista

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../home/styleHome.css">
    <link rel="stylesheet" href="styleTeam.css">
    <script src="../home/scriptHome.js" defer></script>
    <!-- Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <title>Team page</title>
</head>

<body>
    <div class="content">
        <nav class="navbar fixed-top navbar-expand-lg" style="background-color: var(--object_color);">
            <div class="container-fluid" style="background-color: var(--object_color);">
                <a class="navbar-brand fs-3" href="#" style="color: var(--brand_color);">GamerStats</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item dropdown" style="margin-top: 6px;">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false" style="color: var(--navbar_textCol);">Games</a>
                            <ul class="dropdown-menu" style="background-color: var(--object_color);">
                                <!-- TODO: aggiungere href per arrivare alle pagine dei giochi-->
                                <li><a class="dropdown-item" href="#" style="color: var(--brand_color);">Valorant</a></li>
                                <li><a class="dropdown-item" href="../Lol/lol.php" style="color: var(--brand_color);">League of Legends</a></li>
                                <!-- <li><hr class="dropdown-divider"></li>
                                 <li><a class="dropdown-item" href="#">Something else here</a></li> 
                                 Possono sempre servire -->
                            </ul>
                        </li>
                        <li class="nav-item" style="margin-left: auto; margin-top: auto;">
                            <a class="btn btn-outline-success" id="homeref" type="button" style=" background-color:var(--object_color);" href="../home/home.php">
                                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                    <path d="M240-200h120v-240h240v240h120v-360L480-740 240-560v360Zm-80 80v-480l320-240 320 240v480H520v-240h-80v240H160Zm320-350Z" />
                                </svg>
                            </a>
                        </li>
                    </ul>
                    <!-- TODO: modificare href e vari dettagli del signup e login-->
                    <ul class="navbar-nav mb-2 mb-lg-0">
                        <li class="nav-item">
                            <form class="d-flex" role="search">
                                <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search"
                                    style=" background-color:var(--object_color);">
                                <button class="btn btn-outline-success" id="search" type="submit"
                                    style=" background-color:var(--object_color);">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#e8eaed">
                                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                                    </svg>
                                </button>
                            </form>
                        </li>
                        <li class="separator" style="color: var(--separator_color);">|</li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="../login/login.html" style="color: var(--brand_color);">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="../signUp/signUp.html" style="color: var(--brand_color);">Sign Up</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container text-center" style="margin-top: 100px; background-color: var(--transparent_col); padding: 15px;">
            <div class="row">
                <div class="col d-flex flex-column align-items-center">
                    <div class="container text-center" style="background-color: rgba(0,0,0,0);">
                        <div class="row">
                            <div class="col">
                                <img src="../assets/profPicture.jpg" class="img-thumbnail" alt="profilePicture" style="width: 200px; height: 200px;">
                            </div>
                            <div class="col">
                                <button style="background-color: rgba(0,0,0,0); color: var(--text_color); border: none; margin-top: 50px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" id='edit' height="24px" viewBox="0 -960 960 960" width="24px">
                                        <path d="M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <input type="text" class="form-control-plaintext text-center" value="NomeUtente" readonly style="color: var(--text_color); margin-top: 10px; font-size: 2rem; font-weight: bold;">
                    <input type="text" class="form-control-plaintext text-center" value="mail@mail.com" readonly style="color: var(--text_color); margin-top: -10px; font-size: 1rem;">
                </div>
                <div class="col d-flex flex-column align-items-center" style="max-width: 2px;">
                    <hr style="width: 2px; border-width:0; background-color: var(--text_color); height: 90%; max-height: 90%;">
                </div>
                <div class="col d-flex flex-column align-items-center">
                    <p style="color: var(--text_color); font-size: 2rem; font-weight: bold;">Teams</p>
                    <div class="accordion" id="accordionExample" style="width: 100%;;">
                        <div class="accordion-item" style="background-color: var(--transparent_col); color: var(--text_color);">
                            <h2 class="accordion-header" style="color: var(--text_color);">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" style="background-color: rgba(0,0,0,0); color: var(--text_color); font-weight: bold;">
                                    Team 1
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionExample" style="color: var(--text_color);">
                                <div class="accordion-body">
                                    <p>Questo e' il nome del primo membro ciao</p>
                                    <p>Questo e' il nome del secondo membro ciao</p>
                                    <p>Questo e' il nome del terzo membro ciao</p>
                                    <p>Questo e' il nome del quarto membro ciao</p>
                                    <p>Questo e' il nome del quinto membro ciao</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" style="background-color: var(--transparent_col); color: var(--text_color);">
                            <h2 class="accordion-header" style="color: var(--text_color);">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo" style="background-color: rgba(0,0,0,0); color: var(--text_color); font-weight: bold;">
                                    Team 2
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample" style="color: var(--text_color);">
                                <div class="accordion-body">
                                    <p>Questo e' il nome del primo membro ciao</p>
                                    <p>Questo e' il nome del secondo membro ciao</p>
                                    <p>Questo e' il nome del terzo membro ciao</p>
                                    <p>Questo e' il nome del quarto membro ciao</p>
                                    <p>Questo e' il nome del quinto membro ciao</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" style="background-color: var(--transparent_col); color: var(--text_color);">
                            <h2 class="accordion-header" style="color: var(--text_color);">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree" style="background-color: rgba(0,0,0,0); color: var(--text_color); font-weight: bold;">
                                    Team 3
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#accordionExample" style="color: var(--text_color);">
                                <div class="accordion-body">
                                    <p>Questo e' il nome del primo membro ciao</p>
                                    <p>Questo e' il nome del secondo membro ciao</p>
                                    <p>Questo e' il nome del terzo membro ciao</p>
                                    <p>Questo e' il nome del quarto membro ciao</p>
                                    <p>Questo e' il nome del quinto membro ciao</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" style="background-color: var(--transparent_col); color: var(--text_color);">
                            <h2 class="accordion-header" style="color: var(--text_color);">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour" style="background-color: rgba(0,0,0,0); color: var(--text_color); font-weight: bold;">
                                    Team 4
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#accordionExample" style="color: var(--text_color);">
                                <div class="accordion-body">
                                    <p>Questo e' il nome del primo membro ciao</p>
                                    <p>Questo e' il nome del secondo membro ciao</p>
                                    <p>Questo e' il nome del terzo membro ciao</p>
                                    <p>Questo e' il nome del quarto membro ciao</p>
                                    <p>Questo e' il nome del quinto membro ciao</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container text-center" style="margin-top: 10px; background-color: var(--transparent_col); padding: 15px;">
            <p style="font-size: 2rem; font-weight: bold; color: var(--text_color);">Stats</p>
            <p style="font-weight: bold; color: var(--text_color);">Qui ci vanno le statistiche SIUUUUUUUUUU</p>
        </div>
    </div>
</body>
</html>