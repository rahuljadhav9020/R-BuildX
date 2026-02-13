<thead>
    <tr>
        <th>Order ID</th>
        <th>Customer</th>
        <th>Vehicle</th>
        <th>Location</th>
        <th>Purpose</th>
        <th>Rate</th>
        <th>Order Date</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($pending_orders as $order): ?>
    <tr>
        <td>#<?php echo $order['order_id']; ?></td>
        <td>
            <?php echo htmlspecialchars($order['customer_name']); ?><br>
            <?php echo htmlspecialchars($order['mobile']); ?>
        </td>
        <td><?php echo htmlspecialchars($order['vehicle_type']); ?></td>
        <td><?php echo htmlspecialchars($order['location']); ?></td>
        <td><?php echo htmlspecialchars($order['purpose']); ?></td>
        <td>₹<?php echo number_format($order['rate'], 2); ?>/-</td>
        <td><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></td>
        <td>
            <form method="POST" class="approve-form">
                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                <input type="date" name="completion_date" required min="<?php echo date('Y-m-d'); ?>">
                <input type="time" name="completion_time" required>
                <button type="submit" name="approve_order">Approve</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody> 