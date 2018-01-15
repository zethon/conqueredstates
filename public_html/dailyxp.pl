use strict;

my $population = 471854;
my $govt_income = 1481999442;
my $military_budget = 1;
my $cl = 3.212;
my $pf = 8.785;

my $branch_mp = 89562;
my $branch_xp = 0;


# 0 - 10
my $troop_moral = ($cl/2) + ($pf/2);
print ("troop moral: $troop_moral\n");

# per person military budget
# America: 1736.00
# Australia: 880.00
# England: 706.766
# France: 739.00
# New Zealand: 281.93
# South Africa: 80.33
# Bolivia: 14.46
# Chad: 6.93
# Sri Lanka: 29.97
# Congo: 1.65

# range $1 - $5k
my $ppmb = ($govt_income * $military_budget)/$population;
$ppmb = 5000 if ($ppmb > 5000);
print ("per person military budget: $ppmb\n");

# 0 - 100
my $military_proportion = $branch_mp/$population;
print ("percent of pop in branch: $military_proportion\n");

my $newxp = (($troop_moral/10)*33) + (($ppmb/5000)*33) + ($military_proportion*33);
print ("XP: $newxp\n");