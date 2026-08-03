আমি FuelDeskPro প্রজেক্টের docs/Reports/ ফোল্ডারটি আপনার রিপোর্ট তৈরির জন্য একটি সম্পূর্ণ গাইডলাইন তৈরি করছি। আপনি যেহেতু জানতে চাইলে তিনটি রিপোর্ট তৈরির জন্য কিভাবে prompts দিতে পারেন, তা নিচে ধাপে ধাপে দেওয়া হলো।

## 🔍 ব্যস্ত **প্রোজেক্টের বর্তমান অবস্থা**
- আপনার `docs/Reports/` ফোল্ডারে তিনটি রিপোর্ট পিডিএফ ইতিমধ্যে موجود:
  1. **DateToDateAllStatement.pdf**
  2. **MonthlyCollection&ExpenseSummary.pdf**
  3. **MonthlyExpenseSummaryItemWise.pdf**

প্রজেক্টের মূল ডাটাবেজ এবং মডিউল ইতিমধ্যে তৈরি করা আছে।

---

## ✅ **রিপোর্ট তৈরির জন্য ধাপে ধাপে গাইডলাইন**

### **রিপোর্ট ১: Date To Date All Statement** 
(তারিখ থেকে তারিখ পর্যন্ত সম্পূর্ণ বিবরণী - আয়/ব্যয়/স্টক)

**প্রম্পট (Prompt):**
```
আমাকে FuelDeskPro প্রজেক্টের জন্য একটি 
"Date To Date All Statement" রিপোর্ট তৈরি করতে হবে।

ইনপুট: Start Date এবং End Date

রিপোর্টে ম Educator হবে:
১। Fuel Sales Statement:
   - নজিল রিডিং থেকে মোট সেল JSON
   - ফুয়েল প্রকার অনুযায়ী (FuelTypeID wise)

২। Expenses Statement:
   - ত Drake তারিখের মধ্যে সব ব্যয়ের বিবরণ
   - ব্যয়ের ক্যাটাগর Annotate অনুযায়ী গ্রুপ করা

৩। Collections Statement:
   - ক্যাশ কালেকশন (CashCollection)
   - কাস্টমার কালেকশন (CustomerCollection)
   - আন্যান্য কালেকশন (OthersCollection)

৪। Fuel Purchase Statement:
   - Fuel Purchase এন্ট্রি সমূহ

৫। Stock Statement:
   - Tank Reading অনুযায়ী স্টক পরিসংখ্যান

রিপোর্ট ফরম্যাট: HTML টেবিল (PDF প্রিন্ট/friendly)
সাজানো: FuelDeskPro প্রজেক্টের মডিউল স্ট্রাকচার অনুযায়ী

```

---

### **রিপোর্ট ২: Monthly Collection & Expense Summary**
(মাসিক আয় ও ব্যয়ের সারসংক্ষেপ)

**প্রম্পট:**
```
আমাকে FuelDeskPro প্রজেক্টের জন্য 
"Monthly Collection & Expense Summary" রিপোর্ট তৈরি করতে হবে।

ইনপুট: Month (month-year) সিলেক্ট করা যাবে

রিপোর্টের ফরম্যাট:
১। Monthly Summary (Header):
   - মাসের নাম
   - মোট কালেকশন (সকল ধরনের)
   - মোট ব্যয়

২। Collection Details:
   - Cash Collection মাসিক সর্বমোট
   - Customer Due Collection মাসিক সর্বমোট
   - Others Collection মাসিক সর্বমোট
   - Fuel Sales (Nozzle Reading 기준)

৩। Expense Details:
   - Fuel Purchase এর ব্যয়
   - Operational Expense (Expense টেবিল থেকে)
   - Expense Category অনুযায়ী গ্রুপ

৪। Balance:
   - কালেকশন - ব্যয় = ব্যালেন্স

প্রিন্ট: প্রতিটি সেকশন আলাদা আলাদা টেবিল
```

---

### **রিপোর্ট ৩: Monthly Expense Summary Item Wise**
(মাসিক ব্যয় আইটেমভিত্তিক বিস্তারিত)

**প্রম্পট:**
```
FuelDeskPro প্রজেক্টের জন্য 
"Monthly Expense Summary Item Wise" রিপোর্ট তৈরি করতে হবে।

ইনপুট: 
- মাস (Month-Year)
- Optional: Category-wise filter

রিপোর্ট স্ট্রাকচার:
১। Summary Section:
   - মোট ব্যয়ের পরিমাণ
   - ব্যয়ের ধরন অনুযায়ী শতকরা ভাগ

২। Item Wise Breakdown:
   - Expense Category অনুযায়ী গ্রুপিং
   - প্রতিটি ক্যাটাগরির অধীনে Particulars/Items তালিকা
   - প্রতিটি আইটেমের মোট ব্যয়

৩। তাদের নিচে:
   - Date | ParticularID | ExpenseCategory | Amount | Remarks

৪। Summary Footer:
   - প্রতিটি ক্যাটাগরির সর্বমোট
   - সর্বমোট ব্যয়

ডাটা সোর্স:
- trx_expense (প্রধান)
- mst_expensecategory (ক্যাটাগরি নাম)
- mst_expenseparticular (পার্টিকুলার নাম)
```

---

### **রিপোর্ট ফাইল লোকেশন**
তোমার ফাইল কাঠামো:
```
modules/
├── operations/
│   ├── expense.php                      [সদ্যের บ變更 필요]
│   ├── cash_collection.php
│   ├── customer_due.php
│   └── ... (অন্যান্য মডিউল)
docs/
└── Reports/
    ├── DateToDateAllStatement.php       [নতুন - Report 1]
    ├── MonthlyCollection ExpenseSummary.php  [নতুন - Report 2]
    └── MonthlyExpenseSummaryItemWise.php    [নতুন - Report 3]
```

---

## 📋 **কোডিং স্টেপ বাই স্টেপ (Implementation Plan)**

### Step ১: রিপোর্ট মডিউল তৈরি
```bash
। modules/operations/date_to_date_statement.php
। modules/operations/monthly_collection_expense_summary.php
। modules/operations/monthly_expense_item_wise.php
```

### Step ২: প্রতিটি ফাইলের জন্য:

**A. HTML Structure:**
```php
<?php include('../includes/header.php'); ?>
<div class="container">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <!-- Report Title -->
          <h2>Report Name</h2>
        </div>
        <div class="card-body">
          <!-- Filter Form -->
          <form method="GET">
            <input type="date" name="start_date">
            <input type="date" name="end_date">
            <button type="submit" name="generate">Generate</button>
          </form>
          
          <!-- Report Table -->
          <?php if(isset($_GET['generate'])): ?>
            <?php include('report_query.php'); ?>
            <table class="table">
              <!-- Dynamic Data -->
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include('../includes/footer.php'); ?>
```

**B. Query Logic (`report_query.php` এ অথবা সেম ফাইলে):**
```php
<?php
$objQuery = Query::getInstance();

// Start Date & End Date निलমbitos
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Query ১: Fuel Sales
$sqlSales = "SELECT 
    ft.FuelName,
    SUM(nr.SaleQuantity) as TotalQty,
    SUM(nr.SalesAmt) as TotalAmount
  FROM trx_nozzlereading nr
  JOIN mst_fueltype ft ON nr.FuelTypeID = ft.FuelTypeID
  WHERE nr.ReadingDate BETWEEN ? AND ?
  GROUP BY ft.FuelTypeID";

// Query ২: Expenses
$sqlExpense = "SELECT 
    ec.CategoryNameEN,
    SUM(e.Amount) as TotalAmount
  FROM trx_expense e
  JOIN mst_expensecategory ec ON e.ParticularID = ec.ExpenseCategoryID
  WHERE e.ExpenseDate BETWEEN ? AND ?
    AND e.IsActive = 1 AND e.IsDeleted = 0
  GROUP BY ec.ExpenseCategoryID";

// Execute
$sales = $objQuery->index($sqlSales, [$startDate, $endDate]);
$expenses = $objQuery->index($sqlExpense, [$startDate, $endDate]);
?>
```

---

## 🚀 **দ্রাস্তব্য কাজ (Immediate Next Steps)**

নিচের কাজগুলো complete করতে হবে:

১। **প্রতিটি রিপোর্টের জন্য SQL Queries চিহ্নিত করুন**
   - Nozzle Reading, Expense, Collection, Purchase ট্যাবল থেকে 
   - সম্পর্কিত ডাটা angiogenesis کنید

২। **Filter Parameter Design করুন**
   - Date range (start_date, end_date)
   - Month selector (month_year)
   - Fuel Type specific (optional)

৩। **UI Layout Design করুন**
   - Bootstrap Card layout
   - Print CSS
   - Summary Cards (top statistics)
   - Detailed Tables (data table with jQuery DataTables optional)

৪। **PDF Export Option যোগ করুন**
   - মৌলিক PDF পাবলিশ করতে `mpdf` বা `dompdf` ব্যবহার করতে পারেন

---

## 📊 **রিপোর্ট Sample Display Format**

### Report 1: Date To Date All Statement

```
╔══════════════════════════════════════════════╗
║     FUEL DESK PRO - DATE TO DATE STATEMENT      ║
╠══════════════════════════════════════════════╣
║ From: 2026-07-01 | To: 2026-07-31             ║
╠══════════════════════════════════════════════╣
║ 1. FUEL SALES                                 ║
║ ┌──────────────────────────────────────────┐ ║
║ │ Fuel Type │ Qty (L) │ Amount (৳)        │ ║
║ ├──────────────────────────────────────────┤ ║
║ │ LPG       │ 1000.50  │ 65,000.00         │ ║
║ └──────────────────────────────────────────┘ ║
╠══════════════════════════════════════════════╣
║ 2. COLLECTIONS                                ║
║   Cash: 50,000৳ | Customer: 25,000৳ | Others: 5,000৳
╠══════════════════════════════════════════════╣
║ 3. EXPENSES                                   ║
║   Salary: 30,000৳ | Purchase: 15,000৳ | Electricity: 3,000৳
╠══════════════════════════════════════════════╣
║ NET BALANCE: +৳65,000                         ║
╚══════════════════════════════════════════════╝
```

---

## 📝 **Summary**

আপনার প্রজেক্টের ডাটাবেজ ইতিমধ্যে সব ধরনের তথ্য (Sales, Collection, Expense, Purchase, Stock) সংরক্ষণ করে। 
তবে, **রিপোর্ট গেনারেটরের কোড এখনো তৈরি করা হয়নি**।

তোমাকে এটা তৈরি করতে হবে:
১। `DateToDateAllStatement.php` - তারিখের ভিত্তিতে সব ব্যয়/আয়ের বিবরণী
২। `MonthlyCollectionExpenseSummary.php` - মাসিক সারসংক্ষেপ
৩। `MonthlyExpenseSummaryItemWise.php` - ব্যয়ের আইটেমভিত্তিক রিপোর্ট

## 🎯 **প্রম্পট ব্যবহারের সাথে সর্বোচ্চ ফলাফলের জন্য:**

যখন আপনি করুণায় AI কে ರഹLLOW পড়বেন:
- Date range specify করুন (যেমন: "2026-07-01 থেকে 2026-07-31")
- Query শর্ত এবং group by ক্লিয়ারভাবে বলুন
- Output format (HTML table or PDF) উল্লেখ করুন
- Code structure স্পষ্টভাবে চিহ্নিত করুন (module path, includes)

সyste ম系统 Baltimore ifож্য question থাকলে আমাকে জিজ্ঞাসা করতে পারেন বা教学模式Act Mode এ перейти করে specification de যাবেন।