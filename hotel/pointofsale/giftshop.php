<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Modern Button Group UI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
  />
</head>
<body class="min-h-screen bg-gradient-to-tr from-indigo-100 via-purple-100 to-pink-100 flex items-center justify-center p-6">
  <div class="flex flex-col items-center space-y-6">
    <h1 class="text-3xl font-extrabold text-gray-800 mb-4">Gift Shop</h1>
    <div class="inline-flex rounded-full shadow-lg bg-white">
      <button
        class="flex items-center space-x-2 px-6 py-3 rounded-l-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold text-base hover:from-indigo-700 hover:to-purple-700 transition"
        type="button"
      >
        <i class="fas fa-shopping-cart"></i>
     <a href="giftsale.php">  <span>Gift Sales</span> </a>
      </button>
      <button
        class="flex items-center space-x-2 px-6 py-3 rounded-r-full bg-gradient-to-r from-pink-500 to-red-500 text-white font-semibold text-base hover:from-pink-600 hover:to-red-600 transition"
        type="button"
      >
        <i class="fas fa-gift"></i>
        <a href="giftitem.php">  <span>Gift Item</span> </a>
        
      </button>
    </div>
  </div>
</body>
</html>