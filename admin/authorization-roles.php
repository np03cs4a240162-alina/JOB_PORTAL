<?php
require_once '../config/session.php';
$user = requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authorization Roles | JSTACK Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <style>
        
        ::view-transition-group(*),
        ::view-transition-old(*),
        ::view-transition-new(*) {
            animation-duration: 0.25s;
            animation-timing-function: cubic-bezier(0.19, 1, 0.22, 1);
        }

        .matrix-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-top: 30px;
        }

        .roles-matrix {
            width: 100%;
            border-collapse: collapse;
        }

        .roles-matrix th {
            background: #f8fbff;
            padding: 20px;
            text-align: left;
            font-size: 14px;
            color: #0a66c2;
            border-bottom: 2px solid #eef4fb;
        }

        .roles-matrix td {
            padding: 18px 20px;
            border-bottom: 1px solid #f0f4f8;
            font-size: 14px;
            transition: background 0.2s;
        }

        .roles-matrix tr:hover td {
            background: #f9fbff;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
        }

        .allowed { background: #e8f5e9; color: #2e7d32; }
        .denied { background: #ffebee; color: #c62828; }

        .feature-name { font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .feature-name i { color: #666; width: 20px; text-align: center; }

        .role-header { text-align: center !important; width: 100px; }
        .role-status { text-align: center !important; }
    </style>
</head>
<body style="background: #f1f3f6;">

<header class="navbar">
    <h2>JSTACK <span>Admin</span></h2>
    <a href="dashboard.php" style="color:white; text-decoration:none;">← Dashboard</a>
</header>

<div class="container" style="max-width: 900px; margin: 50px auto;">
    <div style="text-align:center; margin-bottom: 30px;">
        <h2 style="font-size: 28px; margin-bottom: 10px;">Security Role Authorization</h2>
        <p style="color: #666;">Detailed permission matrix for each user group in JSTACK.</p>
    </div>

    <div class="matrix-card">
        <table class="roles-matrix">
            <thead>
                <tr>
                    <th>Feature / Capability</th>
                    <th class="role-header">Seeker</th>
                    <th class="role-header">Employer</th>
                    <th class="role-header">Admin</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="feature-name"><i class="fas fa-search"></i> Browse & Search Jobs</td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                </tr>
                <tr>
                    <td class="feature-name"><i class="fas fa-paper-plane"></i> Apply for Vacancies</td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                </tr>
                <tr>
                    <td class="feature-name"><i class="fas fa-plus-circle"></i> Post Job Listings</td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                </tr>
                <tr>
                    <td class="feature-name"><i class="fas fa-users"></i> Review Applicants</td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                </tr>
                <tr>
                    <td class="feature-name"><i class="fas fa-user-shield"></i> User Management</td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                </tr>
                <tr>
                    <td class="feature-name"><i class="fas fa-trash-alt"></i> Force Delete Listings</td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                </tr>
                <tr>
                    <td class="feature-name"><i class="fas fa-chart-line"></i> View System Stats</td>
                    <td class="role-status"><span class="status-badge denied"><i class="fas fa-times"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                    <td class="role-status"><span class="status-badge allowed"><i class="fas fa-check"></i></span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script src="../assets/js/main.js?v=1.2"></script>
</body>
</html>


