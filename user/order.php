<!DOCTYPE html><html><head>
<script>
function hitungTarif(){
let j=parseInt(document.getElementById('jenis').value)||0;
let b=parseFloat(document.getElementById('berat').value)||0;
let jr=parseFloat(document.getElementById('jarak').value)||0;
let bj=jr<=3?5000:jr<=7?10000:jr<=10?15000:20000;
document.getElementById('total').value='Rp '+((j*b)+bj).toLocaleString('id-ID');
}
</script></head><body>
<h2>Order Penjemputan</h2>
<select id="jenis" onchange="hitungTarif()">
<option value="3000">Organik</option>
<option value="5000">Anorganik</option>
<option value="10000">B3</option>
</select>
<input id="berat" oninput="hitungTarif()" placeholder="Berat">
<input id="jarak" oninput="hitungTarif()" placeholder="Jarak">
<input id="total" readonly>
</body></html>