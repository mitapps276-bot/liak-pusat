while ($true) {
    # Cek apakah ada perubahan
    $status = git status --porcelain
    
    if ($status) {
        Write-Host "Mendeteksi perubahan, melakukan sinkronisasi ke GitHub..."
        git add .
        
        $date = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        git commit -m "Auto-update pada $date"
        
        git push origin main
        Write-Host "Berhasil di-push ke GitHub."
    }
    
    # Tunggu 10 detik sebelum mengecek lagi
    Start-Sleep -Seconds 10
}
