function ambilAngka(id) {
    const nilai = Number(document.getElementById(id).value);
    return Number.isFinite(nilai) && nilai > 0 ? nilai : 0;
}

function hitungBiayaJarak(jarak) {
    if (jarak <= 0) {
        return 0;
    }

    if (jarak <= 3) {
        return 5000;
    }

    if (jarak <= 7) {
        return 10000;
    }

    if (jarak <= 10) {
        return 15000;
    }

    return 20000;
}

function formatRupiah(nilai) {
    return "Rp " + nilai.toLocaleString("id-ID");
}

function hitungTarif() {
    const tarifJenis = ambilAngka("jenis");
    const berat = ambilAngka("berat");
    const jarak = ambilAngka("jarak");
    const biayaJarak = hitungBiayaJarak(jarak);
    const total = (tarifJenis * berat) + biayaJarak;

    document.getElementById("totalTarif").value = formatRupiah(total);
}

document.addEventListener("DOMContentLoaded", function () {
    hitungTarif();

    const parameter = new URLSearchParams(window.location.search);
    if (parameter.get("status") === "gagal") {
        alert(parameter.get("pesan") || "Permintaan gagal diproses.");
    }
});
