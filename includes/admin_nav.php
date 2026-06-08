<?php
/**
 * Admin Navigation Component
 */

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$navItems = [
    'index' => ['icon' => '📊', 'label' => t('dashboard')],
    'logs' => ['icon' => '📋', 'label' => t('history')],
    'users' => ['icon' => '👥', 'label' => t('admin')],
];
?>
<!-- Desktop Sidebar -->
<aside class="hidden lg:flex flex-col w-64 bg-white shadow-lg fixed h-full">
    <div class="p-6 border-b">
        <h1 class="text-xl font-bold text-blue-600"><?php echo APP_NAME; ?> <?php echo t('admin'); ?></h1>
    </div>
    <nav class="flex-1 p-4 space-y-2">
        <?php foreach ($navItems as $page => $item): ?>
            <a href="<?php echo $page; ?>.php" 
               class="flex items-center gap-3 px-4 py-3 rounded-lg transition <?php echo $currentPage === $page ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'; ?>">
                <span class="text-xl"><?php echo $item['icon']; ?></span>
                <span class="font-medium"><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
        <div class="pt-4 mt-4 border-t">
            <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100 transition">
                <span class="text-xl">🏠</span>
                <span class="font-medium">Back to App</span>
            </a>
        </div>
    </nav>
</aside>

<!-- Mobile Bottom Nav -->
<nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white shadow-lg border-t z-50">
    <div class="flex justify-around">
        <?php foreach ($navItems as $page => $item): ?>
            <a href="<?php echo $page; ?>.php" 
               class="flex flex-col items-center py-3 px-4 <?php echo $currentPage === $page ? 'text-blue-600' : 'text-gray-500'; ?>">
                <span class="text-xl"><?php echo $item['icon']; ?></span>
                <span class="text-xs mt-1"><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
        <a href="../dashboard.php" class="flex flex-col items-center py-3 px-4 text-gray-500">
            <span class="text-xl">🏠</span>
            <span class="text-xs mt-1">App</span>
        </a>
    </div>
</nav>
