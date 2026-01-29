<?php
session_name("SB_APP_SESSION");
// export_excel.php
session_start();
$conn = mysqli_connect("localhost", "root", "", "portofolio_db");
if(!isset($_SESSION['sb_user'])) { exit(); }

// Nama File Output
$filename = "Summary_Promo_" . date('Ymd') . ".xls";

// Header biar browser download file excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
?>

<table border="1">
    <thead>
        <tr style="background-color: #f0f0f0; font-weight: bold;">
            <th>No</th>
            <th>SB Number</th>
            <th>Promo Name</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Mechanism</th>
            <th>Budget</th>
            <th>Status</th>
            <th>Total Store</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $q = mysqli_query($conn, "
            SELECT s.*, COUNT(c.id) as total_store 
            FROM sales_briefs s 
            LEFT JOIN sb_customers c ON s.id = c.sb_id 
            GROUP BY s.id 
            ORDER BY s.id DESC
        ");
        
        $no = 1;
        while($row = mysqli_fetch_assoc($q)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['sb_number']; ?></td>
            <td><?php echo $row['promo_name']; ?></td>
            <td><?php echo $row['start_date']; ?></td>
            <td><?php echo $row['end_date']; ?></td>
            <td><?php echo $row['promo_mechanism']; ?></td>
            <td><?php echo $row['budget_allocation']; ?></td>
            <td><?php echo $row['status']; ?></td>
            <td><?php echo $row['total_store']; ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>