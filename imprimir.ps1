Add-Type -AssemblyName System.Drawing
Add-Type -AssemblyName System.Windows.Forms

# Ruta del archivo a imprimir
$rutaArchivo = "C:\wamp64\www\SistemaCasino2\recibo.txt"

# Nombre de la impresora
$impresora = "EPSON TM-T(203dpi) Receipt6"

# Crear una instancia del objeto PrintDocument
$printDoc = New-Object System.Drawing.Printing.PrintDocument

# Configurar la impresora
$printDoc.PrinterSettings.PrinterName = $impresora

# Verificar si la impresora está disponible y si tiene papel
$printerStatus = Get-WmiObject -Query "SELECT * FROM Win32_Printer WHERE Name = '$impresora'"

if ($printerStatus.PrinterStatus -eq 7) {
    Write-Host "La impresora no tiene papel."
    exit 1  # Salir con error si no tiene papel
}

# Definir el evento de impresión para enviar el contenido del archivo a la impresora
$printDoc.add_PrintPage({
    param($sender, $e)
    $font = New-Object System.Drawing.Font("Arial Narrow", 10)

    try {
        $streamReader = [System.IO.StreamReader]::new($rutaArchivo)
        $text = $streamReader.ReadToEnd()
        $streamReader.Close()
    } catch {
        Write-Host "Error al leer el archivo: $_"
        exit
    }

    $e.Graphics.DrawString($text, $font, [System.Drawing.Brushes]::Black, [System.Drawing.RectangleF]::new(0, 0, $e.PageBounds.Width, $e.PageBounds.Height))
    $e.HasMorePages = $false
})

# Intentar imprimir el documento
try {
    $printDoc.Print()
} catch {
    Write-Host "Error al imprimir: $_"
    exit 1  # Salir con error si la impresión falla
}
