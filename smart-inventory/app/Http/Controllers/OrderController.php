use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {

            $subtotal = 0;
            $orderItemsData = [];

            foreach ($request->items as $item) {

                // 🔒 LOCK inventory row (prevents race condition)
                $inventory = Inventory::where('branch_id', $request->branch_id)
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->quantity < $item['quantity']) {
                    throw new \Exception('Insufficient stock for product ID: ' . $item['product_id']);
                }

                $product = $inventory->product;

                $lineTotal = $product->price * $item['quantity'];
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $lineTotal,
                ];

                // Deduct stock
                $inventory->quantity -= $item['quantity'];
                $inventory->save();
            }

            $tax = $subtotal * 0.10;
            $total = $subtotal + $tax;

            $order = Order::create([
                'branch_id' => $request->branch_id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            foreach ($orderItemsData as $data) {
                $data['order_id'] = $order->id;
                OrderItem::create($data);
            }

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order
            ]);
        });
    }
}