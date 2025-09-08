<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook Aktivitas - PT Telkom Indonesia</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Tambahkan jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
            --telkom-red: #e60012;
            --telkom-dark-red: #c40010;
            --telkom-light-red: #ff1a2e;
            --telkom-gray: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 50%, #e60012 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(230, 0, 18, 0.1);
            backdrop-filter: blur(10px);
            margin: 20px 0;
            padding: 30px;
            border: 2px solid rgba(230, 0, 18, 0.1);
        }
        
        /* Header Section - Putih dengan logo gambar */
        .header-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: #333;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(230, 0, 18, 0.1);
            box-shadow: 0 8px 25px rgba(230, 0, 18, 0.08);
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(230, 0, 18, 0.02) 10px,
                rgba(230, 0, 18, 0.02) 20px
            );
            animation: slide 20s linear infinite;
        }
        
        @keyframes slide {
            0% { transform: translateX(-50px); }
            100% { transform: translateX(50px); }
        }
        
        .header-section .content {
            position: relative;
            z-index: 2;
        }
        
        .header-section h1 {
            margin: 0;
            font-weight: 700;
            color: #333;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            font-size: 2.2rem;
        }
        
        .header-section p {
            margin: 10px 0 0 0;
            opacity: 0.8;
            font-size: 1.1rem;
            color: #666;
        }
        
        /* Logo Telkom dari gambar */
        .telkom-logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            gap: 0;
        }
        
        .telkom-logo-img {
            height: 80px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            transition: all 0.3s ease;
        }
        
        .telkom-logo-img:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 6px 12px rgba(230, 0, 18, 0.2));
        }

        /* Profile Section */
        .profile-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            border: 2px solid rgba(230, 0, 18, 0.1);
            box-shadow: 0 8px 25px rgba(230, 0, 18, 0.08);
        }

        .profile-section h5 {
            color: var(--telkom-red);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .profile-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: rgba(230, 0, 18, 0.05);
            border-radius: 8px;
            border-left: 4px solid var(--telkom-red);
        }

        .profile-item strong {
            color: var(--telkom-red);
            min-width: 100px;
            margin-right: 10px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(230, 0, 18, 0.08);
            overflow-x: auto;
            border: 1px solid rgba(230, 0, 18, 0.1);
        }
        
        .table {
            margin: 0;
            border-radius: 10px;
            overflow: hidden;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background: linear-gradient(45deg, var(--telkom-red), var(--telkom-dark-red));
            color: white;
            border: none;
            padding: 20px 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-size: 0.85rem;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        
        .table thead th:first-child {
            border-top-left-radius: 10px;
        }
        
        .table thead th:last-child {
            border-top-right-radius: 10px;
        }
        
        .table tbody td {
            padding: 18px 15px;
            vertical-align: top;
            border: 1px solid rgba(230, 0, 18, 0.08);
            border-top: none;
            transition: all 0.3s ease;
            word-wrap: break-word;
            overflow-wrap: break-word;
            hyphens: auto;
            line-height: 1.6;
            font-size: 0.9rem;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe8e8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 0, 18, 0.12);
        }
        
        .table tbody tr:nth-child(even) {
            background-color: rgba(248, 249, 250, 0.6);
        }
        
        .table tbody tr:last-child td:first-child {
            border-bottom-left-radius: 10px;
        }
        
        .table tbody tr:last-child td:last-child {
            border-bottom-right-radius: 10px;
        }
        
        /* Pengaturan lebar kolom yang lebih proporsional dan rapi */
        .col-no { 
            width: 60px; 
            min-width: 60px;
            max-width: 60px;
        }
        .col-date { 
            width: 120px; 
            min-width: 120px;
            max-width: 120px;
        }
        .col-activity-in { 
            width: 35%; 
            min-width: 250px;
        }
        .col-constraint-in { 
            width: 20%; 
            min-width: 180px;
        }
        .col-activity-out { 
            width: 35%; 
            min-width: 250px;
        }
        .col-constraint-out { 
            width: 10%; 
            min-width: 120px;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-info {
            background: linear-gradient(45deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }
        
        .badge-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }
        
        .badge-warning {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }
        
        .badge-danger {
            background: linear-gradient(45deg, var(--telkom-red), var(--telkom-dark-red));
            color: white;
            box-shadow: 0 2px 8px rgba(230, 0, 18, 0.3);
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.8);
        }
        
        .status-masuk { background-color: #28a745; }
        .status-keluar { background-color: var(--telkom-red); }
        .status-kendala { background-color: #ffc107; }
        
        .activity-text {
            font-size: 0.9rem;
            line-height: 1.5;
            color: #333;
            text-align: justify;
            text-justify: inter-word;
        }
        
        .constraint-text {
            font-size: 0.85rem;
            line-height: 1.4;
        }
        
        .date-info {
            text-align: center;
            padding: 5px;
        }
        
        .date-main {
            font-weight: bold;
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }
        
        .date-day {
            color: #666;
            font-size: 0.75rem;
            font-style: italic;
        }
        
        .no-data-cell {
            color: #999;
            font-style: italic;
            font-size: 0.85rem;
            text-align: center;
        }
        
        .constraint-ok {
            color: #28a745;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .constraint-issue {
            color: #dc3545;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #fff 0%, rgba(230, 0, 18, 0.05) 100%);
            border-radius: 12px;
            border-left: 5px solid var(--telkom-red);
            box-shadow: 0 4px 15px rgba(230, 0, 18, 0.08);
        }
        
        .table-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .btn-telkom {
            background: linear-gradient(45deg, var(--telkom-red), var(--telkom-dark-red));
            border: none;
            color: white;
            border-radius: 10px;
            padding: 10px 24px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(230, 0, 18, 0.2);
        }
        
        .btn-telkom:hover {
            background: linear-gradient(45deg, var(--telkom-dark-red), var(--telkom-red));
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(230, 0, 18, 0.4);
            color: white;
        }
        
        .number-cell {
            text-align: center;
            font-weight: bold;
            font-size: 1rem;
            color: var(--telkom-red);
            background: rgba(230, 0, 18, 0.05);
            border-radius: 8px;
            margin: 5px;
            padding: 8px;
        }
        
        @media (max-width: 1400px) {
            .col-activity-in,
            .col-activity-out {
                width: 32%;
                min-width: 220px;
            }
            .col-constraint-in {
                width: 18%;
                min-width: 160px;
            }
            .col-constraint-out {
                width: 12%;
                min-width: 110px;
            }
        }
        
        @media (max-width: 1200px) {
            .main-container {
                padding: 25px;
            }
            
            .table-container {
                padding: 20px;
            }
            
            .col-activity-in,
            .col-activity-out {
                width: 30%;
                min-width: 200px;
            }
            .col-constraint-in {
                width: 20%;
                min-width: 150px;
            }
            .col-constraint-out {
                width: 15%;
                min-width: 100px;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                padding: 20px;
            }
            
            .header-section {
                padding: 20px;
            }
            
            .header-section h1 {
                font-size: 1.8rem;
            }
            
            .table-container {
                padding: 15px;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .header-controls {
                justify-content: center;
            }
            
            .table thead th {
                font-size: 0.7rem;
                padding: 15px 10px;
            }
            
            .table tbody td {
                padding: 15px 10px;
                font-size: 0.8rem;
            }
            
            .col-no { 
                width: 50px; 
                min-width: 50px;
            }
            .col-date { 
                width: 100px; 
                min-width: 100px;
            }
            .col-activity-in { 
                width: 28%; 
                min-width: 180px;
            }
            .col-constraint-in { 
                width: 22%; 
                min-width: 140px;
            }
            .col-activity-out { 
                width: 28%; 
                min-width: 180px;
            }
            .col-constraint-out { 
                width: 18%; 
                min-width: 100px;
            }
            
            .telkom-logo-img {
                height: 60px;
            }

            .profile-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header Section - Putih dengan logo gambar -->
            <div class="header-section">
                <div class="content">
                    <div class="telkom-logo-container">
                        <img src="../assets/img/logo_telkom.png" class="telkom-logo-img">
                    </div>
                    <h1><i class="fas fa-clipboard-list me-3" style="color: var(--telkom-red);"></i>Logbook Aktivitas</h1>
                    <p>Sistem Pencatatan Aktivitas Harian</p>
                </div>
            </div>

            <!-- Profile Section -->
            <div class="profile-section">
                <h5><i class="fas fa-user-circle"></i>Informasi Peserta</h5>
                <div class="profile-info">
                    <div class="profile-item">
                        <strong>Nama:</strong>
                        <span id="profileName">John Doe</span>
                    </div>
                    <div class="profile-item">
                        <strong>NIM/ID:</strong>
                        <span id="profileId">123456789</span>
                    </div>
                    <div class="profile-item">
                        <strong>Asal Instansi:</strong>
                        <span id="profileInstitution">Universitas ABC</span>
                    </div>
                    <div class="profile-item">
                        <strong>Unit Kerja:</strong>
                        <span id="profileUnit">IT Development</span>
                    </div>
                </div>
            </div>
            
            <!-- Table Section -->
            <div class="table-container">
                <div class="table-header">
                    <h5 class="mb-0 text-dark"><i class="fas fa-table me-2 text-danger"></i>Data Logbook Aktivitas</h5>
                    <div class="header-controls">
                        <button class="btn btn-telkom btn-sm" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                        <span class="badge badge-info" id="totalRecords">
                            <i class="fas fa-database me-1"></i>Total: 0 Records
                        </span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="col-no">No</th>
                                <th class="col-date">Tanggal</th>
                                <th class="col-activity-in">Aktivitas Masuk</th>
                                <th class="col-constraint-in">Kendala Masuk</th>
                                <th class="col-activity-out">Aktivitas Keluar</th>
                                <th class="col-constraint-out">Kendala Keluar</th>
                            </tr>
                        </thead>
                        <tbody id="logbookTableBody">
                            <!-- Data akan diisi oleh JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Pagination" class="mt-4">
                    <ul class="pagination justify-content-center" id="pagination">
                        <!-- Pagination akan diisi oleh JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data profil pengguna
        const userProfile = {
            name: "Asya Herawati Putri",
            id: "123456789",
            institution: "Universitas Bina Sarana Informatika",
            unit: "Finance dan HC"
        };

        // Data dummy untuk demonstrasi
        let logbookData = [
            {
                id: 1,
                date: '2024-01-15',
                activityIn: 'Melakukan pengecekan sistem server Telkom dan database pagi hari, memastikan semua service backbone network berjalan normal dengan monitoring real-time',
                constraintIn: 'Server database core network mengalami delay response pada segm en Jakarta-Bandung',
                activityOut: 'Menyelesaikan backup data harian sistem billing dan membuat laporan aktivitas network monitoring untuk tim management',
                constraintOut: 'Tidak ada kendala'
            },
            {
                id: 2,
                date: '2024-01-16',
                activityIn: 'Koordinasi dengan team network operation untuk maintenance fiber optic link Surabaya-Malang dan persiapan dokumentasi',
                constraintIn: 'Tidak ada kendala',
                activityOut: 'Testing konektivitas setelah maintenance dan dokumentasi perubahan konfigurasi router core network',
                constraintOut: 'Packet loss 0.2% pada link backup'
            },
            {
                id: 3,
                date: '2024-01-17',
                activityIn: 'Melakukan monitoring traffic network dan analisis performa sistem core banking Telkom untuk memastikan stabilitas layanan',
                constraintIn: 'Tidak ada kendala',
                activityOut: 'Membuat laporan harian dan dokumentasi incident yang terjadi selama shift kerja',
                constraintOut: 'Tidak ada kendala'
            }
        ];
        
        let currentPage = 1;
        const itemsPerPage = 10;

        // Initialize profile data
        function initializeProfile() {
            document.getElementById('profileName').textContent = userProfile.name;
            document.getElementById('profileId').textContent = userProfile.id;
            document.getElementById('profileInstitution').textContent = userProfile.institution;
            document.getElementById('profileUnit').textContent = userProfile.unit;
        }
        
        function renderTable(data = logbookData) {
            console.log('Rendering table with data:', data);
            
            const tbody = document.getElementById('logbookTableBody');
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedData = data.slice(start, end);
            
            tbody.innerHTML = '';
            
            if (paginatedData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada data yang ditemukan</h5>
                            <p class="text-muted mb-0">Silakan tambah entry baru untuk memulai</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            paginatedData.forEach((item, index) => {
                const row = `
                    <tr>
                        <td>
                            <div class="number-cell">${start + index + 1}</div>
                        </td>
                        <td class="date-info">
                            <div class="date-main">${formatDate(item.date)}</div>
                            <div class="date-day">${getDayName(item.date)}</div>
                        </td>
                        <td>
                            <div class="activity-text">
                                <span class="status-dot status-masuk"></span>
                                ${item.activityIn || 'Tidak ada aktivitas'}
                            </div>
                        </td>
                        <td>
                            <div class="constraint-text">
                                ${item.constraintIn === 'Tidak ada kendala' || !item.constraintIn 
                                    ? '<div class="constraint-ok"><i class="fas fa-check-circle me-1"></i>Tidak ada kendala</div>'
                                    : `<div class="constraint-issue"><i class="fas fa-exclamation-triangle me-1"></i>${item.constraintIn}</div>`
                                }
                            </div>
                        </td>
                        <td>
                            <div class="activity-text">
                                <span class="status-dot status-keluar"></span>
                                ${item.activityOut || '<span class="no-data-cell">Belum ada aktivitas</span>'}
                            </div>
                        </td>
                        <td>
                            <div class="constraint-text">
                                ${item.constraintOut === 'Tidak ada kendala' || !item.constraintOut
                                    ? '<div class="constraint-ok"><i class="fas fa-check-circle me-1"></i>OK</div>'
                                    : `<div class="constraint-issue"><i class="fas fa-exclamation-triangle me-1"></i>${item.constraintOut}</div>`
                                }
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            
            updateTotalRecords(data.length);
            renderPagination(data.length);
        }
        
        function formatDate(dateString) {
            try {
                const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
                return new Date(dateString).toLocaleDateString('id-ID', options);
            } catch (error) {
                console.error('Error formatting date:', error);
                return dateString;
            }
        }
        
        function getDayName(dateString) {
            try {
                const options = { weekday: 'long' };
                return new Date(dateString).toLocaleDateString('id-ID', options);
            } catch (error) {
                console.error('Error getting day name:', error);
                return '';
            }
        }
        
        function updateTotalRecords(count) {
            const totalRecordsElement = document.getElementById('totalRecords');
            if (totalRecordsElement) {
                totalRecordsElement.innerHTML = `
                    <i class="fas fa-database me-1"></i>Total: ${count} Records
                `;
            }
        }
        
        function renderPagination(totalItems) {
            const pagination = document.getElementById('pagination');
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            pagination.innerHTML = '';
            
            if (totalPages <= 1) return;
            
            // Previous button
            pagination.innerHTML += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1})" style="color: var(--telkom-red);">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                pagination.innerHTML += `
                    <li class="page-item ${currentPage === i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i})" 
                           style="${currentPage === i ? 'background-color: var(--telkom-red); border-color: var(--telkom-red);' : 'color: var(--telkom-red);'}">${i}</a>
                    </li>
                `;
            }
            
            // Next button
            pagination.innerHTML += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1})" style="color: var(--telkom-red);">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
        }
        
        function changePage(page) {
            const totalPages = Math.ceil(logbookData.length / itemsPerPage);
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            renderTable();
        }
        
        // Fungsi export ke PDF - Diperbaiki untuk mengatasi kolom terpotong
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    // Gunakan orientasi landscape untuk lebih banyak ruang horizontal
    const doc = new jsPDF('landscape', 'mm', 'a4');

    // Header PDF
    doc.setFontSize(16);
    doc.text("Logbook Aktivitas - PT Telkom Indonesia", 14, 20);

    // Data Profil
    doc.setFontSize(12);
    doc.text("Informasi Peserta:", 14, 35);
    
    doc.setFontSize(11);
    doc.text(`Nama: ${userProfile.name}`, 14, 45);
    doc.text(`NIM/ID: ${userProfile.id}`, 14, 52);
    doc.text(`Asal Instansi: ${userProfile.institution}`, 14, 59);
    doc.text(`Unit Kerja: ${userProfile.unit}`, 14, 66);

    // Tanggal Export
    const currentDate = new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    doc.text(`Tanggal Export: ${currentDate}`, 14, 73);

    // Persiapkan data untuk tabel dengan text yang dipendekkan jika perlu
    const tableData = logbookData.map((item, index) => [
        index + 1,
        formatDate(item.date),
        // Batasi panjang text untuk aktivitas
        (item.activityIn || 'Tidak ada aktivitas').length > 100 
            ? (item.activityIn || 'Tidak ada aktivitas').substring(0, 100) + '...'
            : (item.activityIn || 'Tidak ada aktivitas'),
        (item.constraintIn || 'Tidak ada kendala').length > 50
            ? (item.constraintIn || 'Tidak ada kendala').substring(0, 50) + '...'
            : (item.constraintIn || 'Tidak ada kendala'),
        (item.activityOut || 'Belum ada aktivitas').length > 100
            ? (item.activityOut || 'Belum ada aktivitas').substring(0, 100) + '...'
            : (item.activityOut || 'Belum ada aktivitas'),
        (item.constraintOut || 'Tidak ada kendala').length > 50
            ? (item.constraintOut || 'Tidak ada kendala').substring(0, 50) + '...'
            : (item.constraintOut || 'Tidak ada kendala')
    ]);

    // Buat tabel dengan autoTable - disesuaikan untuk landscape
    doc.autoTable({
        head: [['No', 'Tanggal', 'Aktivitas Masuk', 'Kendala Masuk', 'Aktivitas Keluar', 'Kendala Keluar']],
        body: tableData,
        startY: 85,
        margin: { left: 14, right: 14 }, // Margin kiri dan kanan
        styles: {
            fontSize: 8,
            cellPadding: 2,
            overflow: 'linebreak',
            halign: 'left',
            valign: 'top',
            lineColor: [200, 200, 200],
            lineWidth: 0.1
        },
        headStyles: {
            fillColor: [230, 0, 18],
            textColor: [255, 255, 255],
            fontSize: 9,
            fontStyle: 'bold',
            halign: 'center'
        },
        // Sesuaikan lebar kolom untuk landscape (total lebar sekitar 267mm)
        columnStyles: {
            0: { cellWidth: 15, halign: 'center' }, // No
            1: { cellWidth: 25, halign: 'center' }, // Tanggal  
            2: { cellWidth: 75 }, // Aktivitas Masuk
            3: { cellWidth: 40 }, // Kendala Masuk
            4: { cellWidth: 75 }, // Aktivitas Keluar
            5: { cellWidth: 37 }  // Kendala Keluar
        },
        alternateRowStyles: {
            fillColor: [248, 249, 250]
        },
        tableWidth: 'auto',
        theme: 'grid'
    });

    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(128, 128, 128);
        // Sesuaikan posisi footer untuk landscape
        doc.text(`Halaman ${i} dari ${pageCount}`, 250, 200);
        doc.text('PT Telkom Indonesia - Logbook Aktivitas', 14, 200);
    }

    // Simpan PDF
    const fileName = `Logbook_${userProfile.name.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
    doc.save(fileName);
    
    showToast('Data berhasil di-export ke PDF!', 'success');
}

        function showToast(message, type = 'success') {
            const toastContainer = document.createElement('div');
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1055';
            
            const bgColor = type === 'success' ? 'var(--telkom-red)' : '#dc3545';
            
            toastContainer.innerHTML = `
                <div class="toast show" role="alert">
                    <div class="toast-header text-white" style="background: ${bgColor};">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                        <strong class="me-auto">PT Telkom Indonesia</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            document.body.appendChild(toastContainer);
            
            setTimeout(() => {
                if (document.body.contains(toastContainer)) {
                    document.body.removeChild(toastContainer);
                }
            }, 3000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...');
            initializeProfile();
            renderTable();
        });
    </script>
</body>
</html>
