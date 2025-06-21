<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'save') {
            // Save calculation
            $stmt = $pdo->prepare("INSERT INTO product_calculations (user_id, product_name, price, cost, tax_rate, sales_per_minute) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([
                $user['id'],
                $_POST['product_name'],
                $_POST['price'],
                $_POST['cost'],
                $_POST['tax_rate'],
                $_POST['sales_per_minute']
            ])) {
                $message = 'Calculation saved successfully!';
            }
        } elseif ($_POST['action'] == 'delete') {
            // Delete calculation
            $stmt = $pdo->prepare("DELETE FROM product_calculations WHERE id = ? AND user_id = ?");
            $stmt->execute([$_POST['calc_id'], $user['id']]);
            $message = 'Calculation deleted successfully!';
        }
    }
}

// Get user's saved calculations
$stmt = $pdo->prepare("SELECT * FROM product_calculations WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$calculations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Profit Calculator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f9fafb;
            line-height: 1.6;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1f2937;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-logout {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .calculator-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #1f2937;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        .input-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .results-section {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
        }

        .result-row:not(:last-child) {
            border-bottom: 1px solid #e5e7eb;
        }

        .result-label {
            color: #6b7280;
        }

        .result-value {
            font-weight: 600;
        }

        .result-value.positive {
            color: #059669;
        }

        .result-value.negative {
            color: #dc2626;
        }

        .main-result {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .projections-section {
            background: linear-gradient(135deg, #ecfdf5 0%, #dbeafe 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .projections-title {
            text-align: center;
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
        }

        .projection-row {
            background: white;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-save {
            width: 100%;
            padding: 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-save:hover {
            background: #059669;
        }

        .saved-calculations {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .calc-item {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            position: relative;
        }

        .calc-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .calc-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }

        .calc-date {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .calc-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .calc-detail {
            font-size: 0.9rem;
            color: #4b5563;
        }

        .calc-actions {
            display: flex;
            gap: 10px;
        }

        .btn-load {
            background: #3b82f6;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-load:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }

        .empty-state {
            text-align: center;
            color: #6b7280;
            padding: 40px;
            font-style: italic;
        }

        .calc-profit {
            font-weight: bold;
            color: #10b981;
            font-size: 1.1rem;
        }

        .calc-profit.negative {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">📊 Profit Calculator</div>
            <div class="navbar-user">
                <span>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</span>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Calculator Section -->
            <div class="calculator-section">
                <h2 class="section-title">💰 Calculate Profit</h2>
                
                <form method="POST" id="calculatorForm">
                    <input type="hidden" name="action" value="save">
                    
                    <div class="input-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" name="product_name" placeholder="Enter product name" required>
                    </div>

                    <div class="input-group">
                        <label for="price">Product Price ($)</label>
                        <input type="number" id="price" name="price" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="input-group">
                        <label for="cost">Product Cost ($)</label>
                        <input type="number" id="cost" name="cost" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="input-group">
                        <label for="tax_rate">Tax Rate (%)</label>
                        <input type="number" id="tax_rate" name="tax_rate" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="input-group">
                        <label for="sales_per_minute">Sales per Minute</label>
                        <input type="number" id="sales_per_minute" name="sales_per_minute" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="results-section">
                        <div class="result-row">
                            <span class="result-label">Tax per unit:</span>
                            <span class="result-value negative" id="taxPerUnit">$0.00</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Profit per unit:</span>
                            <span class="result-value" id="profitPerUnit">$0.00</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label main-result">Profit per Minute:</span>
                            <span class="result-value main-result" id="profitPerMinute">$0.00</span>
                        </div>
                    </div>

                    <div class="projections-section">
                        <div class="projections-title">Revenue Projections</div>
                        <div class="projection-row">
                            <span>1 Hour:</span>
                            <span id="profit1Hour">$0.00</span>
                        </div>
                        <div class="projection-row">
                            <span>12 Hours:</span>
                            <span id="profit12Hours">$0.00</span>
                        </div>
                        <div class="projection-row">
                            <span>24 Hours:</span>
                            <span id="profit24Hours">$0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-save">💾 Save Calculation</button>
                </form>
            </div>

            <!-- Saved Calculations Section -->
            <div class="saved-calculations">
                <h2 class="section-title">📋 Saved Calculations</h2>
                
                <?php if (empty($calculations)): ?>
                    <div class="empty-state">
                        No saved calculations yet.<br>
                        Calculate and save your first product!
                    </div>
                <?php else: ?>
                    <?php foreach ($calculations as $calc): ?>
                        <div class="calc-item">
                            <div class="calc-header">
                                <div class="calc-name"><?php echo htmlspecialchars($calc['product_name']); ?></div>
                                <div class="calc-date"><?php echo date('M j, Y', strtotime($calc['created_at'])); ?></div>
                            </div>
                            
                            <div class="calc-details">
                                <div class="calc-detail">
                                    <strong>Price:</strong> $<?php echo number_format($calc['price'], 2); ?>
                                </div>
                                <div class="calc-detail">
                                    <strong>Cost:</strong> $<?php echo number_format($calc['cost'], 2); ?>
                                </div>
                                <div class="calc-detail">
                                    <strong>Tax Rate:</strong> <?php echo number_format($calc['tax_rate'], 2); ?>%
                                </div>
                                <div class="calc-detail">
                                    <strong>Sales/min:</strong> <?php echo number_format($calc['sales_per_minute'], 2); ?>
                                </div>
                            </div>

                            <?php
                                // Calculate profit values
                                $taxPerUnit = $calc['price'] * ($calc['tax_rate'] / 100);
                                $profitPerUnit = $calc['price'] - $calc['cost'] - $taxPerUnit;
                                $profitPerMinute = $profitPerUnit * $calc['sales_per_minute'];
                                $profitClass = $profitPerMinute >= 0 ? '' : 'negative';
                            ?>

                            <div class="calc-profit <?php echo $profitClass; ?>">
                                Profit per minute: $<?php echo number_format($profitPerMinute, 2); ?>
                            </div>

                            <div class="calc-actions">
                                <button type="button" class="btn-load" onclick="loadCalculation(<?php echo htmlspecialchars(json_encode($calc)); ?>)">
                                    Load
                                </button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this calculation?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="calc_id" value="<?php echo $calc['id']; ?>">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Real-time calculation functionality
        function updateCalculations() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const cost = parseFloat(document.getElementById('cost').value) || 0;
            const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            const salesPerMinute = parseFloat(document.getElementById('sales_per_minute').value) || 0;

            // Calculate values
            const taxPerUnit = price * (taxRate / 100);
            const profitPerUnit = price - cost - taxPerUnit;
            const profitPerMinute = profitPerUnit * salesPerMinute;

            // Update display
            document.getElementById('taxPerUnit').textContent = '$' + taxPerUnit.toFixed(2);
            document.getElementById('profitPerUnit').textContent = '$' + profitPerUnit.toFixed(2);
            document.getElementById('profitPerMinute').textContent = '$' + profitPerMinute.toFixed(2);

            // Update projections
            document.getElementById('profit1Hour').textContent = '$' + (profitPerMinute * 60).toFixed(2);
            document.getElementById('profit12Hours').textContent = '$' + (profitPerMinute * 60 * 12).toFixed(2);
            document.getElementById('profit24Hours').textContent = '$' + (profitPerMinute * 60 * 24).toFixed(2);

            // Update colors based on profit
            const profitElement = document.getElementById('profitPerUnit');
            const profitMinuteElement = document.getElementById('profitPerMinute');
            
            if (profitPerUnit >= 0) {
                profitElement.className = 'result-value positive';
                profitMinuteElement.className = 'result-value main-result positive';
            } else {
                profitElement.className = 'result-value negative';
                profitMinuteElement.className = 'result-value main-result negative';
            }
        }

        // Load calculation data into form
        function loadCalculation(calc) {
            document.getElementById('product_name').value = calc.product_name;
            document.getElementById('price').value = calc.price;
            document.getElementById('cost').value = calc.cost;
            document.getElementById('tax_rate').value = calc.tax_rate;
            document.getElementById('sales_per_minute').value = calc.sales_per_minute;
            
            updateCalculations();
        }

        // Add event listeners for real-time updates
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['price', 'cost', 'tax_rate', 'sales_per_minute'];
            inputs.forEach(id => {
                document.getElementById(id).addEventListener('input', updateCalculations);
            });
            
            // Initial calculation
            updateCalculations();
        });

        // Form validation
        document.getElementById('calculatorForm').addEventListener('submit', function(e) {
            const productName = document.getElementById('product_name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const cost = parseFloat(document.getElementById('cost').value);
            
            if (!productName) {
                alert('Please enter a product name.');
                e.preventDefault();
                return;
            }
            
            if (price < 0 || cost < 0) {
                alert('Price and cost cannot be negative.');
                e.preventDefault();
                return;
            }
            
            if (price <= cost) {
                if (!confirm('Warning: Your price is less than or equal to your cost. This will result in a loss. Do you want to continue?')) {
                    e.preventDefault();
                    return;
                }
            }
        });
    </script>
</body>
</html>