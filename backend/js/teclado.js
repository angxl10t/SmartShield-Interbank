
document.addEventListener("DOMContentLoaded", () => {
    const teclado = document.getElementById("teclado");
    const password = document.getElementById("password");

    let usandoEspeciales = false;  
    let mayusculas = false;        

    const filasLetras = [
        ["0","9","1","7","4","5","6","8","2","3"],
        ["q","w","e","r","t","y","u","i","o","p"],
        ["SHIFT","a","s","d","f","g","h","j","k","l"],
        ["SYM","z","x","c","v","b","n","m","BACK"]
    ];

    const filasSimbolos = [
        ["!","@","#","$","%","&","*","(",")","/"],
        ["?","¿","¡","+","-","=","_","[","]","{"],
        ["SHIFT",".",",",":",";","\"","'","\\","|"],
        ["ABC","<",">","^","`","~","BACK"]
    ];

    function renderTeclado() {
        teclado.innerHTML = "";

        const filas = usandoEspeciales ? filasSimbolos : filasLetras;

        filas.forEach(fila => {
            const filaDiv = document.createElement("div");
            filaDiv.className = "fila-teclado";

            fila.forEach(valor => {
                const tecla = document.createElement("div");
                tecla.classList.add("tecla");

                let etiqueta = valor;

                if (valor === "SHIFT") {
                    etiqueta = "⇧";
                    tecla.classList.add("tecla-funcion", "tecla-shift");
                    if (mayusculas) tecla.classList.add("shift-activo");

                } else if (valor === "SYM") {
                    etiqueta = "#+=";
                    tecla.classList.add("tecla-funcion");

                } else if (valor === "ABC") {
                    etiqueta = "ABC";
                    tecla.classList.add("tecla-funcion");

                } else if (valor === "BACK") {
                    etiqueta = "⌫";
                    tecla.classList.add("tecla-funcion", "tecla-borrar");
                }

                if (!usandoEspeciales && /^[a-z]$/i.test(valor)) {
                    etiqueta = mayusculas ? valor.toUpperCase() : valor.toLowerCase();
                }

                tecla.innerText = etiqueta;
                tecla.onclick = () => {

                    if (valor === "SHIFT") {
                        mayusculas = !mayusculas;
                        renderTeclado();
                        return;
                    }

                    if (valor === "SYM") {
                        usandoEspeciales = true;
                        mayusculas = false;
                        renderTeclado();
                        return;
                    }

                    if (valor === "ABC") {
                        usandoEspeciales = false;
                        mayusculas = false;
                        renderTeclado();
                        return;
                    }

                    if (valor === "BACK") {
                        password.value = password.value.slice(0, -1);
                        return;
                    }

                    let caracter = valor;

                    if (!usandoEspeciales && /^[a-z]$/i.test(valor)) {
                        caracter = mayusculas ? valor.toUpperCase() : valor.toLowerCase();
                    }

                    password.value += caracter;
                };

                filaDiv.appendChild(tecla);
            });

            teclado.appendChild(filaDiv);
        });
    }

    renderTeclado();

    password.addEventListener("focus", () => {
        teclado.style.display = "block";
    });
});

function togglePassword() {
    const input = document.getElementById("password");
    input.type = input.type === "password" ? "text" : "password";
}
