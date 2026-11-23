const numericFormatter = new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 6,
});

export function exportNumber(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : null;
}

function displayValue(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return typeof value === 'number'
        ? numericFormatter.format(value)
        : String(value);
}

function safeFilename(value) {
    return String(value)
        .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '-')
        .replace(/\s+/g, '-')
        .slice(0, 140);
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    document.body.append(link);
    link.click();
    link.remove();

    window.setTimeout(() => URL.revokeObjectURL(url), 60000);
}

async function downloadXlsx(data) {
    const module = await import('exceljs');
    const ExcelJS = module.default || module;

    const workbook = new ExcelJS.Workbook();

    workbook.creator = 'IPMS PLN UP3 Palu';
    workbook.created = new Date();

    const sheet = workbook.addWorksheet('Data KPI');
    const columnCount = data.headers.length;

    sheet.mergeCells(1, 1, 1, columnCount);
    sheet.getCell(1, 1).value = data.title;
    sheet.getCell(1, 1).font = {
        name: 'Calibri',
        size: 16,
        bold: true,
        color: { argb: 'FF125D72' },
    };
    sheet.getRow(1).height = 28;

    sheet.mergeCells(2, 1, 2, columnCount);
    sheet.getCell(2, 1).value = data.context;
    sheet.getCell(2, 1).alignment = {
        wrapText: true,
        vertical: 'middle',
    };
    sheet.getRow(2).height = 42;

    sheet.mergeCells(3, 1, 3, columnCount);
    sheet.getCell(3, 1).value =
        `Diekspor: ${new Date().toLocaleString('id-ID')} | ` +
        `${data.rows.length} baris`;

    sheet.addRow([]);
    const header = sheet.addRow(data.headers);
    header.height = 32;

    header.eachCell((cell) => {
        cell.font = {
            name: 'Calibri',
            bold: true,
            color: { argb: 'FFFFFFFF' },
        };
        cell.fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF125D72' },
        };
        cell.alignment = {
            vertical: 'middle',
            wrapText: true,
        };
    });

    data.rows.forEach((values, index) => {
        // String disimpan sebagai teks, bukan formula Excel.
        const row = sheet.addRow(values.map((value) =>
            value === undefined ? null : value
        ));

        row.eachCell({ includeEmpty: true }, (cell) => {
            cell.font = {
                name: 'Calibri',
                size: 11,
                color: { argb: 'FF334155' },
            };

            cell.alignment = {
                vertical: 'top',
                wrapText: true,
            };

            if (index % 2 === 1) {
                cell.fill = {
                    type: 'pattern',
                    pattern: 'solid',
                    fgColor: { argb: 'FFF4F8FB' },
                };
            }

            if (typeof cell.value === 'number') {
                cell.numFmt = '#,##0.######';
            }
        });
    });

    sheet.columns.forEach((column, index) => {
        column.width = index === 0
            ? 7
            : /Nama|Indikator/.test(data.headers[index])
                ? 42
                : 20;
    });

    sheet.views = [{
        state: 'frozen',
        ySplit: 5,
    }];

    sheet.autoFilter = {
        from: { row: 5, column: 1 },
        to: { row: 5, column: columnCount },
    };

    const buffer = await workbook.xlsx.writeBuffer();

    downloadBlob(
        new Blob([buffer], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        }),
        `${safeFilename(data.filename)}.xlsx`
    );
}

async function downloadPdf(data) {
    const [{ jsPDF }, { autoTable }] = await Promise.all([
        import('jspdf'),
        import('jspdf-autotable'),
    ]);

    const doc = new jsPDF({
        orientation: 'landscape',
        unit: 'mm',
        format: 'a4',
    });

    const width = doc.internal.pageSize.getWidth();
    const height = doc.internal.pageSize.getHeight();

    // Judul rata tengah.
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);

    const titleLines = doc.splitTextToSize(
        data.title,
        width - 28
    );

    doc.text(titleLines, width / 2, 17, {
        align: 'center',
    });

    // Informasi ULP, tahun, bulan, dan pencarian rata tengah.
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);

    const contextLines = doc.splitTextToSize(
        data.context,
        width - 28
    );

    const contextY = 17 + titleLines.length * 6;

    doc.text(contextLines, width / 2, contextY, {
        align: 'center',
    });

    // Tabel dengan garis lengkap seperti PDF Laporan Kinerja.
    autoTable(doc, {
        startY: contextY + contextLines.length * 4 + 5,

        margin: {
            top: 14,
            right: 14,
            bottom: 18,
            left: 14,
        },

        head: [data.headers],
        body: data.rows.map(row => row.map(displayValue)),

        theme: 'grid',
        showHead: 'everyPage',
        rowPageBreak: 'avoid',

        tableLineColor: [0, 0, 0],
        tableLineWidth: 0.2,

        styles: {
            font: 'helvetica',
            fontSize: data.headers.length > 10 ? 7 : 8,
            cellPadding: 2.5,
            overflow: 'linebreak',
            valign: 'top',
            textColor: [0, 0, 0],
            fillColor: [255, 255, 255],
            lineColor: [0, 0, 0],
            lineWidth: 0.2,
        },

        headStyles: {
            fillColor: [255, 255, 255],
            textColor: [0, 0, 0],
            fontStyle: 'bold',
            halign: 'center',
            lineColor: [0, 0, 0],
            lineWidth: 0.2,
        },

        bodyStyles: {
            fillColor: [255, 255, 255],
        },
    });

    const pages = doc.getNumberOfPages();

    for (let page = 1; page <= pages; page += 1) {
        doc.setPage(page);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(80, 80, 80);

        doc.text(
            'IPMS PLN - UP3 Palu',
            14,
            height - 8
        );

        doc.text(
            `Halaman ${page} / ${pages}`,
            width - 14,
            height - 8,
            { align: 'right' }
        );
    }

    doc.save(`${safeFilename(data.filename)}.pdf`);
}

export function mountKpiExport(root, getData) {
    const bar = document.createElement('div');
    bar.className = 'kpi-export-bar';

    bar.innerHTML = `
        <div class="kpi-export-menu">
            <button
                type="button"
                class="kpi-export-trigger"
                aria-expanded="false"
                disabled
            >
                <span aria-hidden="true">↓</span>
                <span>Download</span>
                <span aria-hidden="true">▾</span>
            </button>

            <div class="kpi-export-options" hidden>
                <button
                    type="button"
                    class="kpi-export-option"
                    data-format="pdf"
                >
                    <span class="kpi-export-format is-pdf">PDF</span>
                    <span>Download PDF</span>
                </button>

                <button
                    type="button"
                    class="kpi-export-option"
                    data-format="xlsx"
                >
                    <span class="kpi-export-format is-xlsx">XLSX</span>
                    <span>Download Excel</span>
                </button>
            </div>
        </div>
    `;

    const actionBar = root.querySelector('[data-kpi-actions]');

if (actionBar) {
    actionBar.append(bar);
} else {
    root.prepend(bar);
}

    const trigger = bar.querySelector('.kpi-export-trigger');
    const options = bar.querySelector('.kpi-export-options');

    options.id = `${root.id}-export-options`;
    trigger.setAttribute('aria-controls', options.id);

    let available = false;
    let downloading = false;

    function close() {
        options.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    }

    function updateButton() {
        trigger.disabled = !available || downloading;
        trigger.setAttribute('aria-busy', String(downloading));
    }

    trigger.addEventListener('click', () => {
        options.hidden = !options.hidden;
        trigger.setAttribute(
            'aria-expanded',
            String(!options.hidden)
        );
    });

    document.addEventListener('click', (event) => {
        if (!bar.contains(event.target)) close();
    });

    bar.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
            trigger.focus();
        }
    });

    bar.addEventListener('focusout', (event) => {
        if (!bar.contains(event.relatedTarget)) close();
    });

    options.querySelectorAll('[data-format]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (downloading || !available) return;

            const data = getData();

            if (!data?.rows?.length) return;

            close();
            downloading = true;
            updateButton();

            try {
                if (button.dataset.format === 'pdf') {
                    await downloadPdf(data);
                } else {
                    await downloadXlsx(data);
                }
            } catch (error) {
                console.error('Ekspor data gagal:', error);

                const text =
                    'File belum berhasil dibuat. Coba kembali atau ' +
                    'periksa koneksi jika modul unduhan belum termuat.';

                if (typeof window.ipmsNotify === 'function') {
                    await window.ipmsNotify({
                        icon: 'error',
                        title: 'Unduhan gagal',
                        text,
                    });
                } else {
                    window.alert(text);
                }
            } finally {
                downloading = false;
                updateButton();

                if (!trigger.disabled) trigger.focus();
            }
        });
    });

    return {
        setAvailable(value) {
            available = Boolean(value);
            if (!available) close();
            updateButton();
        },
    };
}