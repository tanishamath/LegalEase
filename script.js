document.addEventListener("DOMContentLoaded", function () {
    
    /*** LOGIN FUNCTIONALITY 
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault();
            
            let email = document.getElementById("email").value.trim();
            let password = document.getElementById("password").value.trim();

            if (!email || !password) {
                alert("Please enter both email and password.");
                return;
            }

            let users = JSON.parse(localStorage.getItem("users")) || [];
            let user = users.find(user => user.email === email && user.password === password);

            if (user) {
                let dashboardURL = email.endsWith("@client.com") ? "client-dashboard.html" :
                                   email.endsWith("@lawyer.com") ? "lawyer-dashboard.html" :
                                   email.endsWith("@judge.com") ? "judge-dashboard.html" : "";

                if (!dashboardURL) {
                    alert("Invalid email domain.");
                    return;
                }

                alert("Login successful!");
                window.location.href = dashboardURL;
            } else {
                alert("Invalid email or password.");
            }
        });
    }

    /*** REGISTRATION FUNCTIONALITY ***/
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {
            event.preventDefault();
            
            let name = document.getElementById("name").value.trim();
            let email = document.getElementById("email").value.trim();
            let password = document.getElementById("password").value.trim();
            let role = document.getElementById("role")?.value || "";

            if (!name || !email || !password || !role) {
                alert("Please fill in all fields.");
                return;
            }

            let users = JSON.parse(localStorage.getItem("users")) || [];
            if (users.some(user => user.email === email)) {
                alert("Email is already registered. Please use another.");
                return;
            }

            users.push({ name, email, password, role });
            localStorage.setItem("users", JSON.stringify(users));

            alert("Registration successful! You can now login.");
            window.location.href = "login.html";
        });
    }

    /*** FETCH SPECIALIZATIONS FROM DATABASE ***/
    function fetchSpecializations() {
        fetch("get_specializations.php")
            .then(response => response.json())
            .then(data => {
                let dropdown = document.getElementById("specializationDropdown");
                dropdown.innerHTML = `<option value="">Select Specialization</option>`;
                data.forEach(spec => {
                    dropdown.innerHTML += `<option value="${spec}">${spec}</option>`;
                });
            })
            .catch(error => console.error("Error fetching specializations:", error));
    }

    function fetchLawyerDetails(specialization) {
        fetch("get_lawyers.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `specialization=${specialization}`
        })
        .then(response => response.json())
        .then(data => {
            let tableHTML = "<table class='table'>";
            tableHTML += "<tr><th>Lawyer Name</th><th>Lawyer ID</th><th>Lawyer Email</th></tr>";

            data.forEach(lawyer => {
                tableHTML += `<tr>
                    <td>${lawyer.LAWYER_NAME}</td>
                    <td>${lawyer.LAWYER_ID}</td>
                    <td>${lawyer.LAWYER_EMAIL}</td>
                </tr>`;
            });

            tableHTML += "</table>";
            document.getElementById("lawyerTableContainer").innerHTML = tableHTML;
        })
        .catch(error => console.error("Error fetching lawyers:", error));
    }
    document.getElementById("specializationDropdown").addEventListener("change", function () {
        let specialization = this.value;
        if (specialization) {
            fetchLawyerDetails(specialization);
        } else {
            document.getElementById("lawyerTableContainer").innerHTML = "";
        }
    });

    fetchSpecializations();


   
    /*** TYPING EFFECT FUNCTIONALITY ***/
    let text = "Welcome to LegalEase";
    let speed = 100; // Typing speed in ms
    let index = 0;
    let typingText = document.getElementById("typing-text");
    if (typingText) {
        let text = "Welcome to LegalEase";
        let speed = 100;
        let index = 0;
    
        function typeWriter() {
            if (index < text.length) {
                typingText.textContent += text.charAt(index);
                index++;
                setTimeout(typeWriter, speed);
            }
        }
        typeWriter();
    }
    

}
);
