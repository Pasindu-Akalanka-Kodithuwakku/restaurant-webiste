function saveDivAsPDF() {
    const element = document.getElementById("pdf-content");
    element.style.display = "block";
    
    html2canvas(element, { scale: 1.5, useCORS: true }).then(canvas => {
        const imgData = canvas.toDataURL("image/jpeg", 0.6);

        const imgWidth = 190;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        const pageWidth = 210;
        const pageHeight = imgHeight + 20;

        const pdf = new jspdf.jsPDF("p", "mm", [pageWidth, pageHeight]);

        pdf.addImage(imgData, "JPEG", 10, 10, imgWidth, imgHeight);
        pdf.save("Lavor Hub Restaurant Receipt.pdf");
    });
    element.style.display = "none";
}