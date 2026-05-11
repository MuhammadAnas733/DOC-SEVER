# scripts/setup_daily_backup.ps1
# This script sets up a Windows Task Scheduler task to run the AIH backup daily at a specific time.

param (
    [string]$Time = "02:00" # HH:mm format
)

$taskName = "AIH_Daily_Backup"
$phpPath = "C:\Program Files\php\php.exe" 
$scriptPath = "C:\inetpub\wwwroot\records\scripts\auto_backup.php"
$logPath = "C:\inetpub\wwwroot\records\backups\auto_backup_log.txt"

# Check if task already exists
$existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existingTask) {
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
}

# Define the action: Run php script and pipe output to log
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-Command `"$phpPath $scriptPath >> $logPath`""

# Define the trigger: Daily at the specified time
# Extract hour, minute, and second from $Time
if ($Time -match "(\d{1,2}):(\d{2}):?(\d{2})?") {
    $hour = $Matches[1]
    $minute = $Matches[2]
    $second = if($Matches[3]) { $Matches[3] } else { "00" }
} else {
    $hour = 2
    $minute = 0
    $second = 0
}

$trigger = New-ScheduledTaskTrigger -Daily -At "${hour}:${minute}:${second}"

# Define the principal: Run as System (so it runs even if no user is logged in)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

# Register the task
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal

echo "Daily backup task '$taskName' has been rescheduled for $Time."
echo "You can verify this in Task Scheduler (taskschd.msc)."
