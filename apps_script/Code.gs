/**
 * CheckTrack Google Apps Script
 * Web App for Google Sheets integration
 */

// Constants
const SHEET_NAME = "CheckLog";
const ADMIN_EMAIL = "contact@minhazbinsanto.com";
const WEBHOOK_SECRET = "checktrack-secret-2026";
const HEADERS = ["ID", "Name", "Username", "Date", "CheckIn_Time", "CheckOut_Time", "Duration_Min", "Duration_Formatted", "Status", "IP"];

/**
 * Handle POST requests from PHP
 */
function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    
    // Verify secret token
    if (!data.secret || data.secret !== WEBHOOK_SECRET) {
      return ContentService.createTextOutput(JSON.stringify({
        success: false,
        message: "Unauthorized: Invalid secret token"
      })).setMimeType(ContentService.MimeType.JSON);
    }
    
    const action = data.action;
    
    let result;
    
    switch (action) {
      case "checkin":
        result = handleCheckin(data);
        break;
      case "checkout":
        result = handleCheckout(data);
        break;
      case "get_status":
        result = handleGetStatus(data);
        break;
      default:
        result = { success: false, message: "Unknown action: " + action };
    }
    
    return ContentService.createTextOutput(JSON.stringify(result))
      .setMimeType(ContentService.MimeType.JSON);
      
  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      success: false,
      message: error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

/**
 * Handle GET requests (for testing)
 */
function doGet(e) {
  const action = e.parameter.action || "ping";
  
  let result;
  
  switch (action) {
    case "ping":
      const ss = SpreadsheetApp.getActiveSpreadsheet();
      const sheet = ss.getSheetByName(SHEET_NAME);
      result = { 
        success: true, 
        message: "CheckTrack connected", 
        timestamp: new Date().toISOString(),
        sheet_exists: sheet !== null,
        headers_count: HEADERS.length
      };
      break;
    case "test":
      result = handleTest();
      break;
    default:
      result = { success: false, message: "Unknown action: " + action };
  }
  
  return ContentService.createTextOutput(JSON.stringify(result))
    .setMimeType(ContentService.MimeType.JSON);
}

/**
 * Handle checkin action
 */
function handleCheckin(data) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sheet = ss.getSheetByName(SHEET_NAME);
  
  // Create sheet if it doesn't exist
  if (!sheet) {
    sheet = ss.insertSheet(SHEET_NAME);
    sheet.appendRow(HEADERS);
    formatSheet();
  }
  
  // Append row with checkin data
  const row = [
    data.row_id || "",
    data.name || "",
    data.username || "",
    data.date || "",
    data.checkin_time || "",
    "", // CheckOut_Time
    "", // Duration_Min
    "", // Duration_Formatted
    "Active", // Status
    data.ip || ""
  ];
  
  sheet.appendRow(row);
  const rowNumber = sheet.getLastRow();
  
  // Apply formatting
  formatRow(sheet, rowNumber, "Active");
  
  return { success: true, row: rowNumber };
}

/**
 * Handle checkout action
 */
function handleCheckout(data) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  
  if (!sheet) {
    return { success: false, message: "Sheet not found" };
  }
  
  // Find row by ID
  const idColumn = 1; // Column A
  const dataRange = sheet.getDataRange();
  const values = dataRange.getValues();
  
  let foundRow = -1;
  for (let i = 1; i < values.length; i++) {
    if (values[i][0] == data.row_id) {
      foundRow = i + 1; // 1-based row number
      break;
    }
  }
  
  if (foundRow === -1) {
    return { success: false, message: "Row not found for ID: " + data.row_id };
  }
  
  // Update checkout columns
  sheet.getRange(foundRow, 6).setValue(data.checkout_time || ""); // CheckOut_Time
  sheet.getRange(foundRow, 7).setValue(data.duration_minutes || ""); // Duration_Min
  sheet.getRange(foundRow, 8).setValue(data.duration_formatted || ""); // Duration_Formatted
  sheet.getRange(foundRow, 9).setValue("Done"); // Status
  
  // Apply formatting
  formatRow(sheet, foundRow, "Done");
  
  return { success: true, row: foundRow };
}

/**
 * Handle get_status action
 */
function handleGetStatus(data) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  
  if (!sheet) {
    return { success: true, active_count: 0 };
  }
  
  const statusColumn = 9; // Column I
  const dataRange = sheet.getDataRange();
  const values = dataRange.getValues();
  
  let activeCount = 0;
  for (let i = 1; i < values.length; i++) {
    if (values[i][statusColumn - 1] === "Active") {
      activeCount++;
    }
  }
  
  return { success: true, active_count: activeCount };
}

/**
 * Format the entire sheet
 */
function formatSheet() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  
  if (!sheet) return;
  
  // Format header row
  const headerRange = sheet.getRange(1, 1, 1, HEADERS.length);
  headerRange.setBackground("#1a73e8");
  headerRange.setFontColor("#ffffff");
  headerRange.setFontWeight("bold");
  
  // Auto-resize columns
  for (let i = 1; i <= HEADERS.length; i++) {
    sheet.autoResizeColumn(i);
  }
  
  // Format all data rows
  const dataRange = sheet.getDataRange();
  const values = dataRange.getValues();
  
  for (let i = 1; i < values.length; i++) {
    formatRow(sheet, i + 1, values[i][8]); // Status is column 9 (index 8)
  }
}

/**
 * Format a single row based on status
 */
function formatRow(sheet, rowNumber, status) {
  const range = sheet.getRange(rowNumber, 1, 1, HEADERS.length);
  if (status === "Active") {
    range.setBackground("#fff9c4");
  } else if (status === "Done") {
    range.setBackground("#e8f5e9");
  } else {
    range.setBackground("#ffffff");
  }
}

/**
 * Send daily summary email
 */
function sendDailySummary() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  
  if (!sheet) {
    Logger.log("Sheet not found");
    return;
  }
  
  const today = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyy-MM-dd");
  const dataRange = sheet.getDataRange();
  const values = dataRange.getValues();
  
  // Handle case where sheet has no data rows (only header or empty)
  if (values.length <= 1) {
    Logger.log("No data rows found in sheet");
    return;
  }
  
  let totalCheckins = 0;
  let totalMinutes = 0;
  const activeUsers = [];
  
  for (let i = 1; i < values.length; i++) {
    const rowDate = values[i][3]; // Date column (index 3)
    const dateStr = Utilities.formatDate(new Date(rowDate), Session.getScriptTimeZone(), "yyyy-MM-dd");
    
    if (dateStr === today) {
      totalCheckins++;
      totalMinutes += parseInt(values[i][6]) || 0; // Duration_Min
      
      if (values[i][8] === "Active") {
        activeUsers.push(values[i][1]); // Name
      }
    }
  }
  
  const totalHours = Math.floor(totalMinutes / 60);
  const remainingMinutes = totalMinutes % 60;
  
  // Build email body
  let body = "📊 CheckTrack Daily Summary\n";
  body += "📅 Date: " + today + "\n\n";
  body += "📈 Statistics:\n";
  body += "• Total Check-ins: " + totalCheckins + "\n";
  body += "• Total Hours: " + totalHours + "h " + remainingMinutes + "min\n\n";
  
  if (activeUsers.length > 0) {
    body += "⚠️ Still Active (not checked out):\n";
    activeUsers.forEach(name => {
      body += "• " + name + "\n";
    });
  } else {
    body += "✅ All users have checked out";
  }
  
  body += "\n\n---\n";
  body += "This is an automated message from CheckTrack";
  
  // Send email
  MailApp.sendEmail({
    to: ADMIN_EMAIL,
    subject: "CheckTrack Daily Summary - " + today,
    body: body
  });
  
  Logger.log("Daily summary sent to " + ADMIN_EMAIL);
}

/**
 * Handle test action
 */
function handleTest() {
  return {
    success: true,
    message: "Test successful",
    timestamp: new Date().toISOString(),
    sheet_exists: SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SHEET_NAME) !== null
  };
}

/**
 * Create custom menu on open
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('CheckTrack')
    .addItem('Format Sheet', 'formatSheet')
    .addItem('Send Test Email', 'sendTestEmail')
    .addItem('View Today Summary', 'viewTodaySummary')
    .addToUi();
}

/**
 * Send test email
 */
function sendTestEmail() {
  MailApp.sendEmail({
    to: ADMIN_EMAIL,
    subject: "CheckTrack Test Email",
    body: "This is a test email from CheckTrack.\n\nIf you received this, the email integration is working correctly!"
  });
  
  SpreadsheetApp.getUi().alert("Test email sent to " + ADMIN_EMAIL);
}

/**
 * View today summary in dialog
 */
function viewTodaySummary() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(SHEET_NAME);
  
  if (!sheet) {
    SpreadsheetApp.getUi().alert("Sheet not found");
    return;
  }
  
  const today = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), "yyyy-MM-dd");
  const dataRange = sheet.getDataRange();
  const values = dataRange.getValues();
  
  let totalCheckins = 0;
  let totalMinutes = 0;
  const activeUsers = [];
  
  for (let i = 1; i < values.length; i++) {
    const rowDate = values[i][3];
    const dateStr = rowDate instanceof Date ? 
      Utilities.formatDate(rowDate, Session.getScriptTimeZone(), "yyyy-MM-dd") : 
      String(rowDate).split(" ")[0];
    
    if (dateStr === today) {
      totalCheckins++;
      totalMinutes += parseInt(values[i][6]) || 0;
      
      if (values[i][8] === "Active") {
        activeUsers.push(values[i][1]);
      }
    }
  }
  
  const totalHours = Math.floor(totalMinutes / 60);
  const remainingMinutes = totalMinutes % 60;
  
  let message = "Today's Summary (" + today + ")\n\n";
  message += "Total Check-ins: " + totalCheckins + "\n";
  message += "Total Hours: " + totalHours + "h " + remainingMinutes + "min\n\n";
  
  if (activeUsers.length > 0) {
    message += "Still Active:\n";
    activeUsers.forEach(name => {
      message += "• " + name + "\n";
    });
  } else {
    message += "All users have checked out";
  }
  
  SpreadsheetApp.getUi().alert(message);
}
