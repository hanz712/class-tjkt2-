const students = Array.from({length:36}, (_,i)=>({
  name:`Siswa XI TJKT 2 ${String(i+1).padStart(2,"0")}`,
  nis:`26TJKT${String(i+1).padStart(2,"0")}`
}));

const schedules = {
  Senin:[
    ["1","Matematika","Rina Solihat, S.Pd."],["2","Bahasa Indonesia","Edah Rossansen, M.Pd."],["3","Produktif TJKT","Ginanjar Eka Saputra, S.Kom."],["4","Produktif TJKT","Lisaana Khaira, S.Kom."],["5","Bahasa Inggris","Neni Nurhayati, S.Pd."]
  ],
  Selasa:[
    ["1","Pendidikan Agama","Rayhan Nuglantyka, S.Ag."],["2","Informatika","Gin Gin Caesar Marcelina, S.Pd."],["3","Produktif TJKT","Agus Sudrajat, S.Kom."],["4","Matematika","Rina Solihat, S.Pd."],["5","PKK","Suhud Romadhon, S.E."]
  ],
  Rabu:[
    ["1","Produktif TJKT","Decky Muhammad Yasyin, S.T."],["2","Bahasa Indonesia","Edah Rossansen, M.Pd."],["3","Pendidikan Pancasila","Afaf Adilah, S.H."],["4","Produktif TJKT","Ginanjar Eka Saputra, S.Kom."],["5","Bahasa Inggris","Neni Nurhayati, S.Pd."]
  ],
  Kamis:[
    ["1","Matematika","Rina Solihat, S.Pd."],["2","Produktif TJKT","Lisaana Khaira, S.Kom."],["3","Sejarah","Drs. Fitriyadi"],["4","Pendidikan Agama","Rayhan Nuglantyka, S.Ag."],["5","Produktif TJKT","Agus Sudrajat, S.Kom."]
  ],
  Jumat:[
    ["1","PJOK","Suhud Romadhon, S.E."],["2","Produktif TJKT","Decky Muhammad Yasyin, S.T."],["3","Bahasa Inggris","Neni Nurhayati, S.Pd."],["4","Projek","Gin Gin Caesar Marcelina, S.Pd."]
  ]
};

const piket = {
  Senin:["Siswa 01","Siswa 02","Siswa 03","Siswa 04","Siswa 05"],
  Selasa:["Siswa 06","Siswa 07","Siswa 08","Siswa 09","Siswa 10"],
  Rabu:["Siswa 11","Siswa 12","Siswa 13","Siswa 14","Siswa 15"],
  Kamis:["Siswa 16","Siswa 17","Siswa 18","Siswa 19","Siswa 20"],
  Jumat:["Siswa 21","Siswa 22","Siswa 23","Siswa 24","Siswa 25"]
};

function renderStudents(filter=""){
  const tbody=document.querySelector("#studentTable");
  const list=students.filter(s=>s.name.toLowerCase().includes(filter.toLowerCase())||s.nis.toLowerCase().includes(filter.toLowerCase()));
  tbody.innerHTML=list.map((s,i)=>`<tr><td>${i+1}</td><td><b>${s.name}</b></td><td>${s.nis}</td><td><span class="status">Aktif</span></td><td><button class="outline">Edit</button></td></tr>`).join("");
  document.querySelector("#studentCount").textContent=`${list.length} siswa`;
}
function renderSchedule(day="Senin"){
  document.querySelector("#schedule").innerHTML=schedules[day].map(x=>`<div class="schedule-item"><div class="period">${x[0]}</div><div><h3>${x[1]}</h3><p>${x[2]}</p></div></div>`).join("");
  document.querySelectorAll("#dayTabs button").forEach(b=>b.classList.toggle("active",b.dataset.day===day));
}
function renderDays(){
  document.querySelector("#dayTabs").innerHTML=Object.keys(schedules).map((d,i)=>`<button class="${i===0?"active":""}" data-day="${d}">${d}</button>`).join("");
  document.querySelectorAll("#dayTabs button").forEach(b=>b.onclick=()=>renderSchedule(b.dataset.day));
}
function renderPiket(){
  document.querySelector("#piketGrid").innerHTML=Object.entries(piket).map(([day,names])=>`<div class="piket-card"><h3>${day}</h3>${names.map(n=>`<div>👤 ${n}</div>`).join("")}</div>`).join("");
}
function showPage(id){
  document.querySelectorAll(".page").forEach(p=>p.classList.remove("active-page"));
  const page=document.getElementById(id)||document.getElementById("dashboard");
  page.classList.add("active-page");
  document.querySelectorAll(".nav-link").forEach(a=>a.classList.toggle("active",a.dataset.section===id));
  document.querySelector("#pageTitle").textContent=page.querySelector("h1")?.textContent||"Dashboard";
  document.querySelector("#sidebar").classList.remove("open");
  window.scrollTo({top:0,behavior:"smooth"});
}
document.querySelectorAll(".nav-link").forEach(a=>a.addEventListener("click",e=>{e.preventDefault();showPage(a.dataset.section);history.replaceState(null,"","#"+a.dataset.section)}));
document.querySelector("#menuBtn").onclick=()=>document.querySelector("#sidebar").classList.toggle("open");
document.querySelector("#studentSearch").oninput=e=>renderStudents(e.target.value);

const modal=document.querySelector("#studentModal");
document.querySelector("#addStudentBtn").onclick=()=>modal.classList.add("show");
document.querySelector("#closeModal").onclick=()=>modal.classList.remove("show");
document.querySelector("#saveStudent").onclick=()=>{
  const name=document.querySelector("#newName").value.trim(), nis=document.querySelector("#newNis").value.trim();
  if(!name)return alert("Nama siswa wajib diisi.");
  students.push({name,nis:nis||"Belum diisi"});
  renderStudents();
  document.querySelector("#newName").value="";document.querySelector("#newNis").value="";
  modal.classList.remove("show");
};
document.querySelector("#scanBtn").onclick=()=>alert("Scanner QR akan diaktifkan setelah backend PHP + html5-qrcode dipasang.");
document.querySelector("#resetAttendance").onclick=()=>{
  document.querySelector("#hadirStat").textContent="0";
  document.querySelector("#belumStat").textContent="36";
  document.querySelector("#alpaStat").textContent="0";
  document.querySelector("#hadirBarText").textContent="0 / 36";
  document.querySelector("#alpaBarText").textContent="0 / 36";
  document.querySelector("#hadirBar").style.width="0%";
  document.querySelector("#alpaBar").style.width="0%";
};

renderStudents();renderDays();renderSchedule();renderPiket();
const initial=location.hash.replace("#","");
if(initial && document.getElementById(initial)) showPage(initial);
