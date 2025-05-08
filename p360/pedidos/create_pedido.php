// Supongamos que ya tienes $pedidoId y $item = [product_id, quantity]
$base  = $pdo->prepare("SELECT base_price FROM products WHERE id=?");
$base->execute([$item['product_id']]);
$basePrice   = (float)$base->fetchColumn();

// Precio al cliente de price_lists
$prl  = $pdo->prepare("
  SELECT price FROM price_lists
  WHERE user_id=? AND product_id=?
");
$prl->execute([$_SESSION['user_id'], $item['product_id']]);
$clientPrice = (float)$prl->fetchColumn();

// Calcular descuento %
$discountPct = round((1 - $clientPrice / $basePrice) * 100, 2);

// Insertar línea con todos los datos
$ins = $pdo->prepare("
  INSERT INTO pedido_items
    (pedido_id, product_id, quantity, price, base_price, discount_pct)
  VALUES (?, ?, ?, ?, ?, ?)
");
$ins->execute([
  $pedidoId,
  $item['product_id'],
  $item['quantity'],
  $clientPrice,
  $basePrice,
  $discountPct
]);
