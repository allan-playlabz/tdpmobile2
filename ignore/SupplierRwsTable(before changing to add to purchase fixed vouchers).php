<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\I18n\Number;
use Cake\I18n\Time;
use Cake\Http\Client;
use Cake\Utility\Text;
use Cake\Log\Log;
use Cake\Core\Configure;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\Mailer\Email;
use PHPQRcode\QRcode;
use Aws\Resource\Aws;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class SupplierRwsTable extends Table
{
  private $Tickets;
  private $server_url;
  private $api_key;
  private $secret_key;
  private $signature_method;
  private $signature_headers;
  private $rws_ticket_order_item_mapping;
  private $templates = ["offer-200524152456005956_USS1DCHPAMSDISC04M01"=>"uss_ta_ota_dated",
"offer-200524152456005956_USS1DSNPAMSDISC04M01"=>"uss_ta_ota_dated",
"offer-200524152456005956_USS1DADPAMSDISC04M01"=>"uss_ta_ota_dated",
"offer-200520135808001249_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-200520135808001249_USSEX110PAMS0000ED01"=>"uss_express_ta_dated",
"offer-200520135808001249_USSEX20PAMS00000ED01"=>"uss_express_ta_dated",
"offer-200520135808001249_USSEX10PAMS00000ED01"=>"uss_express_ta_dated",
"offer-200520183211001272_SEA1DSNPAMS000004M01"=>"seaa_srv_drf_admission",
"offer-200520183211001272_SEA1DADPAMS000004M01"=>"seaa_srv_drf_admission",
"offer-200520183211001272_SEA1DCHPAMS000004M01"=>"seaa_srv_drf_admission",
"offer-200526200538007883_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200526200538007883_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200526200538007883_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200520183918001273_ACW1DSNPAMS00000ED01"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200520183918001273_ACW1DADPAMS00000ED01"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200520183918001273_ACW1DCHPAMS00000ED01"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200518140353001026_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200518140353001026_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200518140353001026_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200520190805001275_SEA1DSNPAMSDISC04M01"=>"seaa_srv_drf_admission",
"offer-200520190805001275_SEA1DCHPAMSDISC04M01"=>"seaa_srv_drf_admission",
"offer-200520190805001275_SEA1DADPAMSDISC04M01"=>"seaa_srv_drf_admission",
"offer-200519234742001247_USS1DCHPAMSDISC04M01"=>"uss_ta_ota_dated",
"offer-200519234742001247_USS1DADPAMSDISC04M01"=>"uss_ta_ota_dated",
"offer-200519234742001247_USS1DSNPAMSDISC04M01"=>"uss_ta_ota_dated",
"offer-200520191904001287_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200520191904001287_VGV50PAMSR0000006M50"=>"uss_ta_ota_dated",
"offer-200527131905011586_ACW1DADPAMS00000ED01"=>"ta_ota_adventure_cove_water_park_open_dated",
"offer-200527131905011586_ACW1DCHPAMS00000ED01"=>"ta_ota_adventure_cove_water_park_open_dated",
"offer-200527131905011586_ACW1DSNPAMS00000ED01"=>"ta_ota_adventure_cove_water_park_open_dated",
"offer-200520194436001292_USS1DADPAMS000006M01"=>"uss_ta_ota_dated",
"offer-200520194436001292_USS1DCHPAMS000006M01"=>"uss_ta_ota_dated",
"offer-200527220345013700_USSTRCHPAMSVIPNP0TDZ"=>"uss_vip_dated_w_language_timeslot",
"offer-200527220345013700_USSTRADPAMSVIPNP0TDZ"=>"uss_vip_dated_w_language_timeslot",
"offer-200527220345013700_VMV10PAMSUVIP002TD10"=>"uss_discount_rv_ta_open_dated",
"offer-200527220345013700_VMV5PAMSUVIP00002TD5"=>"uss_discount_rv_ta_open_dated",
"offer-200521115423001516_ACW1DCHPAMSDISC04M01"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200521115423001516_ACW1DSNPAMSDISC04M01"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200521115423001516_ACW1DADPAMSDISC04M01"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200527230907013701_USSTRADPAMSVIPP00TD2"=>"uss_vip_dated_w_language_timeslot",
"offer-200527230907013701_USSTRCHPAMSVIPP00TDZ"=>"uss_vip_dated_w_language_timeslot",
"offer-200520140939001250_USSPASNPAMST00006M01"=>"uss_ta_ota_dated",
"offer-200520140939001250_USSPAADPAMST00006M01"=>"uss_ta_ota_dated",
"offer-200520140939001250_USSPACHPAMST00006M01"=>"uss_ta_ota_dated",
"offer-200521115806001519_ACW1DADPAMSDISC04M01"=>"ta_ota_adventure_cove_water_park_open_dated",
"offer-200521115806001519_ACW1DSNPAMSDISC04M01"=>"ta_ota_adventure_cove_water_park_open_dated",
"offer-200521115806001519_ACW1DCHPAMSDISC04M01"=>"ta_ota_adventure_cove_water_park_open_dated",
"offer-200527232418013702_ACWRAYADPAMSDIS CED0Z"=>"ume_ray_encounter_dated",
"offer-200527232418013702_ACW1DADPAMS0DISCTD0Z"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200520160752001256_DOLIDISCHPAMS000ED01"=>"dolphin_discover_dated",
"offer-200520160752001256_DOLIDISSNPAMS000ED01"=>"dolphin_discover_dated",
"offer-200520160752001256_DOLIDISADPAMS000ED01"=>"dolphin_discover_dated",
"offer-200521135045001558_SEA1DSNPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200521135045001558_SEA1DADPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200521135045001558_SEA1DCHPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200527232811013703_ACW1DCHPAMS0DISCTD0Z"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200527232811013703_ACWRAYCHPAMSDISCED0Z"=>"ume_ray_encounter_dated",
"offer-200521135351001560_SEA1DADPAMSGT00DTD01"=>"seaa_srv_drf_admission",
"offer-200521135351001560_SEA1DCHPAMSGT00DTD01"=>"seaa_srv_drf_admission",
"offer-200521135351001560_SEA1DSNPAMSGT00DTD01"=>"seaa_srv_drf_admission",
"offer-200527233017013704_ACW1DSNPAMS0DISCTD0Z"=>"ta_ota_adventure_cove_water_park_dated",
"offer-200527233017013704_ACWRAYSNPAMSDISCED0Z"=>"ume_ray_encounter_dated",
"offer-200520162856001258_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200520162856001258_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200520162856001258_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200527233444013705_USS1DADPAMSJTB00DF0Z"=>"uss_ta_ota_dated",
"offer-200527233444013705_USSENFADPAMSJTBEEDFZ"=>"uss_ta_ota_dated",
"offer-200520165027001261_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200520165027001261_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200520165027001261_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200527233933013713_USS1DCHPAMSJTB00DF0Z"=>"uss_ta_ota_dated",
"offer-200527233933013713_USSENFCHPAMSJTBEEDFZ"=>"uss_ta_ota_dated",
"offer-200523224123005753_PKG1DSC133PAMSCHOOMP"=>"seaa_1d_w_uce_ta_dated",
"offer-200523224123005753_PKG1DSC133PAMSSNOOMP"=>"seaa_1d_w_uce_ta_dated",
"offer-200523224123005753_PKG1DSC133PAMSADOOMP"=>"seaa_1d_w_uce_ta_dated",
"offer-200523224722005755_PKG1DSC133PAMSADODMP"=>"seaa_1d_w_uce_ta_dated",
"offer-200523224722005755_PKG1DSC133PAMSSNODMP"=>"seaa_1d_w_uce_ta_dated",
"offer-200523224722005755_PKG1DSC133PAMSCHODMP"=>"seaa_1d_w_uce_ta_dated",
"offer-200528001052013733_USS1DADPAMS0000P4M0Z"=>"uss_ta_ota_dated",
"offer-200524002300005857_CSCDF6CHTA000000ED05"=>"uss_ta_ota_dated",
"offer-200524002300005857_CSCDF8CHTA000000ED05"=>"uss_ta_ota_dated",
"offer-200524002300005857_CSCDF6ADTA000000ED05"=>"uss_ta_ota_dated",
"offer-200524002300005857_CSCDF8ADTA000000ED05"=>"uss_ta_ota_dated",
"offer-200528001303013734_USS1DCHPAMS0000P4M0Z"=>"uss_ta_ota_dated",
"offer-200524010224005859_USS1DADPAMS0000D6M01"=>"uss_ta_ota_dated",
"offer-200524010224005859_USS1DCHPAMS0000D6M01"=>"uss_ta_ota_dated",
"offer-200907191128058863_USSEXU100PAMSPWTCED1"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU60PAMSWTCPED01"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU50PAMSWTCPED01"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU70PAMSPWTCED01"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU80PAMSPWTCED01"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU90PAMSPWTCED01"=>"uss_express_ta_dated",
"offer-201023164138034943_PKG1DUS132PAMSAD00MP"=>"uss_sea_sdm_open_dated",
"offer-201029120319040375_USSEXU100PAMSWTNPED1"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU90PAMSWTNPED01"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU50PAMSWTNPED01"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU60PAMSWTNPED01"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU70PAMSWTNPED01"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU80PAMSWTNPED01"=>"uss_express_ta_dated",
"offer-201102175455041343_USS1DADPAMS0000006MZ"=>"uss_ta_ota_dated",
"offer-201102175455041343_VGV50PAMSR0000006M50"=>"uss_ta_ota_dated",
"offer-201102180017041346_USS1DADPAMS00000D6MZ"=>"uss_ta_ota_dated",
"offer-201102180017041346_VGV25PAMSR00000D6M2Z"=>"uss_ta_ota_dated",
"offer-201102180921041366_PKG1DUS132PAMSAD0DMP"=>"uss_sea_sdm_open_dated",
"offer-201123113610079168_SEA1DADPTABSRVB11ED1"=>"seaa_srv_drf_admission",
"offer-201123115604079169_SEA1DCHPTABSRVB11ED1"=>"seaa_srv_drf_admission",
"offer-201123115951079170_SEA1DYOPTABSRVB11ED1"=>"seaa_srv_drf_admission",
"offer-201123120247079171_SEA1DSNPTABSRVB11ED1"=>"seaa_srv_drf_admission",
"offer-201214190959086179_USSPKG669TESTALLED01"=>"uss_ta_ota_dated",
"offer-201214190959086179_USSPKG669TESTCH0ED01"=>"uss_ta_ota_dated",
"offer-201214190959086179_USSPKG669TESTSN0ED01"=>"uss_ta_ota_dated",
"offer-201214191212086180_SEAPKG669TESTAD0ED01"=>"seaa_srv_drf_admission",
"offer-201214191212086180_SEAPKG669TESTCH0ED01"=>"seaa_srv_drf_admission",
"offer-201214191212086180_SEAPKG669TESTSN0ED01"=>"seaa_srv_drf_admission",
"offer-201215120406086279_USSPKG669TEST2AED01"=>"dolphin_discover_dated",
"offer-210122121530103070_DOLIDISADPTABSRVED01"=>"dolphin_discover_dated",
"offer-210122121530103070_DOLIDISCHPTABSRVED01"=>"dolphin_discover_dated",
"offer-210122121530103070_DOLIDISSNPTABSRVED01"=>"dolphin_discover_dated",
"offer-210325195022128298_USS1DADPAMSDISC0ED10"=>"uss_ta_ota_dated",
"offer-210325195022128298_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195022128298_USSEX150PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195515128299_USS1DCHPAMS00000ED10"=>"uss_ta_ota_dated",
"offer-210325195515128299_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195515128299_USSEX150PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195912128300_USS1DSNPAMS00000ED10"=>"uss_ta_ota_dated",
"offer-210325195912128300_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195912128300_USSEX150PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210326124912128319_USS1DADPAMSDISC0ED10"=>"uss_ta_ota_dated",
"offer-210326124912128319_USSEXU60PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326124912128319_USSEXU100PAMSWTCED01"=>"uss_express_ta_dated",
"offer-210326125314128320_USS1DCHPAMSDISC0ED10"=>"uss_ta_ota_dated",
"offer-210326125314128320_USSEXU60PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326125314128320_USSEXU100PAMSWTCED01"=>"uss_express_ta_dated",
"offer-210326125528128321_USS1DSNPAMSDISC0ED10"=>"uss_ta_ota_dated",
"offer-210326125528128321_USSEXU60PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326125528128321_USSEXU100PAMSWTCED01"=>"uss_express_ta_dated",
"offer-210416093628134729_SEABTPADPTABPRG0ED02"=>"seaa_srv_drf_admission",
"offer-210416093628134729_SEABTPCHPTABPRG0ED02"=>"seaa_srv_drf_admission",
"offer-210416093628134729_SEABTPSNPTABPRG0ED02"=>"seaa_srv_drf_admission",

"offer-210326125528128321_USSEXU100PAMSWTCED01"=>"uss_express_ta_dated",
"offer-210326125528128321_USSEXU90PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326125528128321_USSEXU60PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326125528128321_USSEXU50PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326125314128320_USSEXU100PAMSWTCED01"=>"uss_express_ta_dated",
"offer-210326125314128320_USSEXU90PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326125314128320_USSEXU60PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326125314128320_USSEXU50PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326124912128319_USSEXU100PAMSWTCED01"=>"uss_express_ta_dated",
"offer-210326124912128319_USSEXU50PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326124912128319_USSEXU60PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210326124912128319_USSEXU90PAMSWTC0ED01"=>"uss_express_ta_dated",
"offer-210325195912128300_USSEX50PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210325195912128300_USSEX40PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210325195912128300_USSEX150PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195912128300_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195515128299_USSEX150PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195515128299_USSEX50PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210325195515128299_USSEX40PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210325195515128299_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195022128298_USSEX50PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210325195022128298_USSEX40PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210325195022128298_USSEX150PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210325195022128298_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU90PAMSWTNPED01"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU80PAMSWTNPED01"=>"uss_express_ta_dated",
"offer-201029120319040375_USSEXU100PAMSWTNPED1"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU90PAMSPWTCED01"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU100PAMSPWTCED1"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU50PAMSWTCPED01"=>"uss_express_ta_dated",
"offer-200907191128058863_USSEXU60PAMSWTCPED01"=>"uss_express_ta_dated",
"offer-200527230907013701_USSTRCHPAMSVIPP00TDZ"=>"uss_vip_dated_w_language_timeslot",
"offer-200527230907013701_USSTRADPAMSVIPP00TD2"=>"uss_vip_dated_w_language_timeslot",
"offer-200520135808001249_USSEX50PAMS00000ED01"=>"uss_express_ta_dated",
"offer-200520135808001249_USSEX110PAMS0000ED01"=>"uss_express_ta_dated",
"offer-200520135808001249_USSEX100PAMS0000ED01"=>"uss_express_ta_dated",
"offer-200520135808001249_USSEX40PAMS00000ED01"=>"uss_express_ta_dated",

"offer-201123120247079171_VRV0PTABSSRVMAXM0TD0"=>"seaa_discount_rv_ta_dated",
"offer-201123115951079170_VRV0PTABSSRVMAXM0TD0"=>"seaa_discount_rv_ta_dated",
"offer-201123115604079169_VRV0PTABSSRVMBXM0TD0"=>"seaa_discount_rv_ta_dated",
"offer-201123113610079168_VRV0PTABSSRVMAXM0TD0"=>"seaa_discount_rv_ta_dated",
"offer-201102180921041366_VMV5PAMSU000000006M0"=>"uss_discount_rv_ta_open_dated",
"offer-201102180921041366_VRV5PAMSS000000006M0"=>"seaa_discount_rv_ta_dated",
"offer-201102180017041346_VGV25PAMSR00000D6M2Z"=>"uss_discount_rv_ta_open_dated",
"offer-201102175455041343_VGV50PAMSR0000006M50"=>"uss_discount_rv_ta_open_dated",
"offer-201029120319040375_VRV5PAMSUUEUNP000TD0"=>"uss_discount_rv_ta_open_dated",
"offer-201023164138034943_VMV5PAMSU000000006M0"=>"uss_discount_rv_ta_open_dated",
"offer-201023164138034943_VRV5PAMSS000000006M0"=>"seaa_discount_rv_ta_dated",
"offer-200528001303013734_VMV5PAMSU0000000P6MZ"=>"uss_discount_rv_ta_open_dated",
"offer-200528001303013734_VMVPAMSUICE00000P4MZ"=>"uss_ice_cream_voucher_ta_open_dated",
"offer-200528001052013733_VMV5PAMSU0000000P6MZ"=>"uss_discount_rv_ta_open_dated",
"offer-200527234725013723_VMVTAORTGFFRCS200DFZ"=>"uss_discount_rv_ta_open_dated",
"offer-200527234725013723_VMVTAORTGFFF2000DF2Z"=>"uss_discount_rv_ta_open_dated",
"offer-200527234725013723_VRVTAORTGFFWB200DF1Z"=>"uss_discount_rv_ta_open_dated",
"offer-200527230907013701_VMV5PAMSUVIP00002TD5"=>"uss_discount_rv_ta_open_dated",
"offer-200527230907013701_VMV10PAMSUVIP002TD10"=>"uss_discount_rv_ta_open_dated",
"offer-200527220345013700_VMV5PAMSUVIP00002TD5"=>"uss_discount_rv_ta_open_dated",
"offer-200527220345013700_VMV10PAMSUVIP002TD10"=>"uss_discount_rv_ta_open_dated",
"offer-200527124648011294_VMV5PAMSS000000004M5"=>"seaa_discount_rv_ta_dated",
"offer-200527124648011294_VMV5PAMSU000000004M5"=>"uss_discount_rv_ta_open_dated",
"offer-200527124648011294_VMV5PAMSA000000004M5"=>"uss_discount_rv_ta_open_dated",
"offer-200527121829010749_VMV10PAMSM000003M10"=>" uss_discount_rv_ta_open_dated",
"offer-200526200538007883_VMV5PAMSUCOVIM0004M0"=>"uss_discount_rv_ta_open_dated",
"offer-200524152456005956_VMV5PAMSUCOVIM0004M0"=>"uss_discount_rv_ta_open_dated",
"offer-200524010224005859_VGV50PAMSR0000006M50"=>"uss_discount_rv_ta_open_dated",
"offer-200521165430001587_VRV12PAMSUDIGIR04M12"=>"uss_discount_rv_ta_open_dated",
"offer-200520194436001292_VGV50PAMSR0000006M50"=>"uss_discount_rv_ta_open_dated",
"offer-200520194436001292_VGV100PAMSR00006M100"=>"uss_discount_rv_ta_open_dated",
"offer-200520191904001287_VGV50PAMSR0000006M50"=>"uss_discount_rv_ta_open_dated",
"offer-200520173032001262_VMV5PAMSS000000004M5"=>"seaa_discount_rv_ta_dated",
"offer-200520173032001262_VMV5PAMSU000000004M5"=>"uss_discount_rv_ta_open_dated",
"offer-200520173032001262_VMV5PAMSA000000004M5"=>"uss_discount_rv_ta_open_dated",
"offer-200520162243001257_VMV10PAMSM0000003M9"=>" uss_discount_rv_ta_open_dated",];/*["offer-200504101615254475_SEA1DCHCPAMS0000ED01"=>"seaa_srv_drf_admission",
"offer-200504101615254475_SEA1DSNCPAMS0000ED01"=>"seaa_srv_drf_admission",
"offer-200504101615254475_SEA1DADCPAMS0000ED01"=>"seaa_srv_drf_admission",
"offer-200504101637254490_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504101637254490_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504101637254490_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504104422255341_SEA1DSNCPAMS0000ED01"=>"seaa_srv_drf_admission",
"offer-200504104422255341_SEA1DCHCPAMS0000ED01"=>"seaa_srv_drf_admission",
"offer-200504104422255341_SEA1DADCPAMS0000ED01"=>"seaa_srv_drf_admission",
"offer-200504105445255666_DOLIDISSNPAMS000ED01"=>"dolphin_discover_dated",
"offer-200504105445255666_DOLIDISADPAMS000ED01"=>"dolphin_discover_dated",
"offer-200504105445255666_DOLIDISCHPAMS000ED01"=>"dolphin_discover_dated",
"offer-200504110103255844_VMV10PAMSM0000003M9"=>"mfs_discount_ta_dated",
"offer-200504111449256273_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504111449256273_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504111449256273_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504131824259908_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504131824259908_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504131824259908_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504132634260151_USS1DCHPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504132634260151_USS1DSNPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504132634260151_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504135651260995_USS1DADPAMS000004M01"=>"uss_ta_ota_dated",
"offer-200504135651260995_USSEX50PAMS00000ED01"=>"uss_express_ta_dated", // uss express
"offer-200504140004261098_USSEX50PAMS00000ED01"=>"uss_ta_ota_dated", // uss express
"offer-200621122518007023_USSPACHPAMSTDISC6M09"=>"uss_srv_drf",
"offer-200621122518007023_USSPASNPAMSTDISC6M09"=>"uss_srv_drf",
"offer-200621122518007023_USSPAADPAMSTDISC6M09"=>"uss_srv_drf",
"offer-200621175600007217_SEA1DSNPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200621175600007217_SEA1DCHPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200621175600007217_SEA1DADPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200621175816007218_SEA1DCHPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200621175816007218_SEA1DSNPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-200621175816007218_SEA1DADPAMSGT000TD01"=>"seaa_srv_drf_admission",
"offer-210207170127084259_USS1DADPAMSJJ0004M01"=>"uss_ta_sdm_open_dated",
"offer-210207170127084259_VMVPAMSUCDJJ000004M0"=>"uss_ice_cream_voucher_ta_open_dated",
"offer-210207170507084260_USS1DCHPAMSJJ0004M01"=>"uss_ta_sdm_open_dated",
"offer-210207170507084260_VMVPAMSUICEJJ00004M0"=>"uss_ice_cream_voucher_ta_open_dated",
"offer-210207171138084265_USSPKG750ADJJ000PAMS"=>"uss_sea_sdm_open_dated",
"offer-210207171138084265_USS1DADPAMSJJ0004M01"=>"uss_ta_sdm_open_dated",
"offer-210207171138084265_SEA1DADPAMSJJ0004M01"=>"seaa_ta_ota_sdm_open_dated",
"offer-210207171138084265_VMV5PAMSUJJ0000004M0"=>"uss_mvtadated",
"offer-210209110855085971_SEAPKG750ADFJ000PAMS"=>"seaa_daycation_srv_admission_drf",
"offer-210209110855085971_SEA1DADPAMSFJ0004M01"=>"seaa_ta_ota_sdm_open_dated",
"offer-210209110855085971_VMV0PAMSCDSRVFJ001M0"=>"seaa_discount_mv_ta_dated",
"offer-210209111813085975_SEAPKG750CHFJ000PAMS"=>"seaa_daycation_srv_admission_drf",
"offer-210209111813085975_VMV0PAMSMCSRVFJ001M0"=>"",
"offer-210209111813085975_SEA1DCHPAMSFJ0004M01"=>"seaa_ta_ota_sdm_open_dated",
"offer-210407155351127944_OAP1DADFITGEN000ED01"=>"",
"offer-210407155351127944_VMV5CWEBUBDL000007D5"=>"uss_mvtadated",
"offer-210407155351127944_VMV5CWEBSBDL000007D5"=>"seaa_discount_mv_ta_dated",
"offer-210407155351127944_VMV5CWEBABDL000007D5"=>"",
"offer-210407165123127990_OAP1DADFITVIP000ED01"=>"",
"offer-210407165123127990_VMV5CWEBUBDL000007D5"=>"uss_mvtadated",
"offer-210407165123127990_VMV5CWEBSBDL000007D5"=>"seaa_discount_mv_ta_dated",
"offer-210407165123127990_VMV5CWEBABDL000007D5"=>"",
"offer-210407171854127995_OAP1DADFITGEN000DB01"=>"",
"offer-210412100349129902_OAP1DADFITGEN000DB01"=>"",
"offer-210412100349129902_VMV5CWEBABDL000007D5"=>"",
"offer-210412100349129902_VMV5CWEBSBDL000007D5"=>"seaa_discount_mv_ta_dated",
"offer-210412100349129902_VMV5CWEBUBDL000007D5"=>"uss_mvtadated",
"offer-210209111813085975_SEAPKG750CHFJ000PAMS"=>"seaa_daycation_srv_admission_drf",
"offer-210209111813085975_VMV0PAMSMCSRVFJ001M0"=>"seaa_discount_mv_ta_dated",
"offer-210209111813085975_SEA1DCHPAMSFJ0004M01"=>"seaa_daycation_srv_admission_drf",
"offer-210205151016083306_USSEX80PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210205174845083437_USSEXU80PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210207120145084049_USSPKG374UEX60NP0PAM"=>"uss_express_ta_dated",
"offer-210207120145084049_USSEXU60PAMSNP00ED01"=>"uss_express_ta_dated",
"offer-210207120145084049_VMV5ICUUEUEUNPPAMED0"=>"uss_mvtadated",
"offer-210207120145084049_USSEXU50PAMSNP00ED01"=>"uss_express_ta_dated",
"offer-210207120145084049_USSPKG374UEX70NP0PAM"=>"uss_express_ta_dated",
"offer-210207120145084049_USSEXU70PAMSNP00ED01"=>"uss_express_ta_dated",
"offer-210207120145084049_USSPKG374UEX80NP0PAM"=>"uss_express_ta_dated",
"offer-210207120145084049_USSEXU80PAMSNP00ED01"=>"uss_express_ta_dated",
"offer-210207120145084049_USSPKG374UEX90NP0PAM"=>"uss_express_ta_dated",
"offer-210207120145084049_USSEXU90PAMSNP00ED01"=>"uss_express_ta_dated",
"offer-210205151016083306_USSEX50PAMS00000ED01"=>"uss_express_ta_dated",
"offer-210205174845083437_USSEXU50PAMS0000ED01"=>"uss_express_ta_dated",
"offer-210205174845083437_USSEXU60PAMS0000ED01"=>"uss_express_ta_dated",
];*//*[
    'product-1402594103916494854'=>'uss_sea_sdm_open_dated',
    'product-565737490275631110'=>'dolphin_observer_dated',
    'VMV5PAMSUJJ0000004M0'=>'uss_mvtadated',
    'SEAPKG750ADFJ000PAMS' =>'uss_sea_sdm_open_dated',
    'USS1DCHPAMS000004M01' => 'uss_srv_drf',
    'USSEX50PAMS00000ED01' =>'uss_mvtadated'
  ];*/

  /* TODO */
  // logging of access
  public function __construct() {
    $this->signature_method = 'HmacSHA256';
    $this->signature_headers = 'x-ca-key,x-ca-nonce,x-ca-signaturemethod';//'x-ca-key,x-ca-nonce,x-ca-signature-method';
    $this->Tickets = TableRegistry::get('Tickets');
    $this->Vouchers = TableRegistry::get('Vouchers');
    $this->ApiPdfs = TableRegistry::get('ApiPdfs');
    $this->RwsProducts = TableRegistry::get('RwsProducts');
    $this->OrderItems = TableRegistry::get('OrderItems');
    $this->Orders = TableRegistry::get('Orders');
    $this->OrdersToComplete = TableRegistry::get('OrdersToComplete');
  }

  public function updatePrice($attraction,$tickets,$connection,$datetime) {

  }

  public function queryAvailability($attraction,$rtickets,$connection,$datetime) {
    $http = new Client();
    $availability = [];
    if ($connection) {
      $this->server_url = $connection->api_url;
      $credentials = json_decode($connection->credentials,true);
      if (!empty($credentials)) {
        $this->api_key = !empty($credentials['api_key']) ? $credentials['api_key'] : '';
        $this->secret_key = !empty($credentials['secret_key']) ? $credentials['secret_key'] : '';
      }
    }
    $tickets = [];
    foreach ($rtickets as $api_sku => $rticket) {
      $api_sku_parts = explode('|',$rticket->api_sku);
      if ($api_sku_parts && count($api_sku_parts) >= 2) {
        $rticket->offer_no = $api_sku_parts[0];
        if (count($api_sku_parts) == 3) {
          $rticket->language = $api_sku_parts[2];
        }
        $tickets[$api_sku_parts[1]] = $rticket;
      }
    }
    $date = $datetime->format('Y-m-d');
    $max_quantity = Configure::read('Default.max_order_quantity');
    $md5 = '';
    $url = '/b2b/product/v1/offer/detail';
    foreach ($tickets as $key => $ticket) {
      $data = [
          "offerNo" => $ticket->offer_no,
          "validTimeFrom" => $date,
          "validTimeTo" => (new Time($datetime))->addDays(1)->format('Y-m-d')
      ];
    
      $headers = $this->_buildHeaders('GET',$url.'?'. http_build_query($data),$date,$md5,$data);
      $this->_logRequest($data,'query_availability');
      $response = $http->get($this->server_url.$url,$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
      if ($response->isOk() && !empty($response->getJson()) && !empty($response->getJson()['result'])) {
        $offer = $response->getJson()['result'];
        // $offerProfile = 
        if ($offer && !empty($offer['offerProfile'])) {
          foreach ($offer['offerProfile']['productGroup'] as $offerGroup) {
            foreach ($offerGroup['productGroup'] as $productGroup) {
              foreach ($productGroup['products'] as $idProduct => $product) {
                if (in_array($product['productNo'], array_keys($tickets))) {
                  $is_found_in_inventory = false;
                  $ticket = $tickets[$product['productNo']];
                  // $api_sku_parts = explode('-',$ticket->api_sku);
                  $language = $ticket->language;
                  $product_no = $offer['offerProfile']['offerNo'].'|'.$product['productNo'];
                  if ($language) {
                    $product_no .= '|'.$language;
                  }
                  $availability[$product_no] = [];
                  if ($offer['offerProfile']['inventories']) {
                    foreach ($offer['offerProfile']['inventories'] as $key => $offer_inventory) {
                      if (is_object($offer_inventory) && $offer_inventory->available != -1 && $offer_inventory->inventoryDate == $date) {
                        $availability[$product_no]['stock'] = $offer_inventory->available;
                        $is_found_in_inventory = true;
                      }
                    }
                  }
                  
                  $availability[$product_no]['min_quantity'] = $productGroup['minProductQuantity'];
                  $availability[$product_no]['max_quantity'] = $productGroup['maxProductQuantity'];
                  foreach ($product['priceRule'] as $price_rule) {
                    if ($price_rule['defaultPrice'] == true) {
                      foreach ($price_rule['productPrice'] as $idTicket => $price) {
                        if ($price['priceDate'] == $date) {
                          if ($price['productInventory'] == -1) {
                            if (!empty($availability[$product_no]['stock'])) {
                              if ($availability[$product_no]['stock'] > $max_quantity) {
                                $availability[$product_no]['stock'] = $max_quantity;
                              }
                            } else {
                              $availability[$product_no]['stock'] = $max_quantity;
                            }
                          } else {
                            $inventory = $price['productInventory'];
                            if ($language) {
                              foreach ($price['inventoryLanguageGroups'] as $key => $inventory_lg) {
                                if ($language == $inventory_lg['language'] || $inventory_lg['language'] == '') {
                                  $inventory = $inventory_lg['available'];
                                }
                              }
                            }
                            if (!empty($availability[$product_no]['stock']) && $availability[$product_no]['stock'] < $inventory) {
                              $availability[$product_no]['stock'] = $availability[$product_no]['stock'];
                            } else {
                              $availability[$product_no]['stock'] = $inventory;
                            }
                          }
                          if ($availability[$product_no]['stock'] < $availability[$product_no]['max_quantity']) {
                            $availability[$product_no]['max_quantity'] = $availability[$product_no]['stock'];
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        } else {
          $this->_logError($url,$data,$headers,$response);
        }
      } else {
        $this->_logError($url,$data,$headers,$response);
      }
    }
    return $availability;
  }

  private function _buildCommonOffers($common_offers,$order_item,$qty,$ticket,$patron_info) {
    $this->RwsProducts = TableRegistry::get('RwsProducts');
    

    $language = null;
    $api_sku = $ticket->api_sku;
    $offer_no = null;
    $api_sku_parts = explode('|',$ticket->api_sku);
    if ($api_sku_parts && count($api_sku_parts) >= 2) {
      $offer_no = $api_sku_parts[0];
      $api_sku = $api_sku_parts[1];
      if (count($api_sku_parts) == 3) {
        $language = $api_sku_parts[2];
      }
    }
    $rws_product = $this->RwsProducts->find()->where(['product_no'=>$api_sku,'offer_no'=>$offer_no])->first();
    // if (count($api_sku_parts) === 3) {
    //   $language = $api_sku_parts[2];
    //   $api_sku = $api_sku_parts[0].'-'.$api_sku_parts[1];
    // }
    if ($rws_product) {
      if (strtolower($rws_product->choice_constrain) == 'single') {
        $attraction_product = [
          "productNo"=> $api_sku,
          "numOfAttraction" => $qty,
          "visitDate" => $order_item->date_of_visit->format('Y-m-d'),
          "comment" => $order_item->id,
          "cardDisplayName" => "",
          "patronInfo" => $patron_info
          ];
          if ($language) {
            $attraction_product['language'] = $language;
          }
        $common_offer = [
            "offerNo" => $offer_no,
            "offerCount" => 1,
            "attractionProducts" => [$attraction_product
            ]
          ];
          $common_offers[$order_item->id] = $common_offer;
      } else {
        if (!empty($common_offers[$offer_no])) {
          $common_offer = $common_offers[$offer_no];
          $attraction_product = [
              "productNo"=> $api_sku,
              "numOfAttraction" => $qty,
              "visitDate" => $order_item->date_of_visit->format('Y-m-d'),
              "comment" => $order_item->id,
              "cardDisplayName" => "",
              "patronInfo" => $patron_info
              ];
          if ($language) {
            $attraction_product['language'] = $language;
          }

          if (!empty($rws_product)) {
            if (!empty($rws_product->choice_constrain) && strtolower($rws_product->choice_constrain) == 'fixed') {
              $common_offer['offerCount'] += $qty;
              $attraction_product['numOfAttraction'] = 1;
            } else {
              $attraction_product['numOfAttraction'] = $qty;
            }
          } 
          $common_offer['attractionProducts'][] = $attraction_product;
          /* add the additional products inside fixed */
          if (!empty($rws_product)) {
            if (!empty($rws_product->choice_constrain) && strtolower($rws_product->choice_constrain) == 'fixed') {
              if (!empty($rws_product->purchase_with)) {
                $purchase_with = json_decode($rws_product->purchase_with,true);
                if (!empty($purchase_with)) {
                  foreach ($purchase_with as $key => $item_plus) {
                    $other_product = [
                    "productNo"=> $item_plus,
                    "numOfAttraction" =>1,
                    "visitDate" => $order_item->date_of_visit->format('Y-m-d'),
                    "comment" => $order_item->id,
                    "cardDisplayName" => "",
                    "patronInfo" => $patron_info
                    ];
                    $common_offer['attractionProducts'][] = $other_product;
                    $this->rws_ticket_order_item_mapping[$rws_product->offer_no.'|'.$item_plus] = $order_item->id;
                  }
                }
              }
            }
          } 
          $common_offers[$offer_no] = $common_offer;

        } else {
          $attraction_product = [
              "productNo"=> $api_sku,
              "numOfAttraction" => $qty,
              "visitDate" => $order_item->date_of_visit->format('Y-m-d'),
              "comment" => $order_item->id,
              "cardDisplayName" => "",
              "patronInfo" => $patron_info
              ];
          if ($language) {
            $attraction_product['language'] = $language;
          }
          $common_offer = [
            "offerNo" => $offer_no,
            "offerCount" => 1,//$order_item->qty,
            "attractionProducts" => [
            ]
          ];
          // debug($rws_product);exit;
          if (!empty($rws_product->choice_constrain) && strtolower($rws_product->choice_constrain) == 'fixed') {
            $common_offer['offerCount'] = $order_item->qty;
            $attraction_product['numOfAttraction'] = 1;
            /* add the additional products inside fixed */
            if (!empty($rws_product)) {
              if (!empty($rws_product->choice_constrain) && strtolower($rws_product->choice_constrain) == 'fixed') {
                if (!empty($rws_product->purchase_with)) {
                  $purchase_with = json_decode($rws_product->purchase_with,true);
                  if (!empty($purchase_with)) {
                    foreach ($purchase_with as $key => $item_plus) {
                      $other_product = [
                      "productNo"=> $item_plus,
                      "numOfAttraction" =>1,
                      "visitDate" => $order_item->date_of_visit->format('Y-m-d'),
                      "comment" => $order_item->id,
                      "cardDisplayName" => "",
                      "patronInfo" => $patron_info
                      ];
                      $common_offer['attractionProducts'][] = $other_product;
                      $this->rws_ticket_order_item_mapping[$rws_product->offer_no.'|'.$item_plus] = $order_item->id;
                    }
                  }
                }
              }
            } 
            $common_offers[$offer_no] = $common_offer;
          }
          $common_offer['attractionProducts'][] = $attraction_product;
          $common_offers[$offer_no] = $common_offer;
        }
      }
    } else {
       $this->_logFailure(['reason'=>'Missing RWS product.','api_sku'=>$ticket->api_sku,'order_id'=>$order_item->order_id],null);
       throw new Exception('Missing Product');
    }
    
    
    return $common_offers;
  }

  public function purchase($order,$order_items,$connection) {
    $TRY_TIMES = 15;
    $this->rws_ticket_order_item_mapping = [];
    $http = new Client();
    $this->Tickets = TableRegistry::get('Tickets');
    $company_name = '';
    $nationality = '';
    $country_code = '';
    if ($connection) {
      $this->server_url = $connection->api_url;
      $credentials = json_decode($connection->credentials,true);
      if (!empty($credentials)) {
        $this->api_key = !empty($credentials['api_key']) ? $credentials['api_key'] : '';
        $this->secret_key = !empty($credentials['secret_key']) ? $credentials['secret_key'] : '';
        $company_name = !empty($credentials['company_name']) ? $credentials['company_name'] : '';
        $nationality = !empty($credentials['nationality']) ? $credentials['nationality'] : '';
        $country_code = !empty($credentials['country_code']) ? $credentials['country_code'] : 'Singapore';
      }
    }
    $common_offers = [];
    $order_details = $order->checkout_info;
    if (!is_array($order_details)) {
      $order_details = json_decode($order_details,true);
    }
    $patron_info = [
            "firstName" => !empty($order_details['last_name']) ? $order_details['last_name'] : '',
            "lastName" => !empty($order_details['first_name']) ? $order_details['first_name'] : '',
            "phoneNo" => !empty($order_details['mobile']) ? $order_details['mobile'] : '',
            "email" => !empty($order_details['email']) ? $order_details['email'] : '',
            "companyName" => $company_name,
            "nationality" => $nationality,
            "countryCode" => $country_code
            ];
    $total = 0;
    // build common offers
    foreach ($order_items as $key => $order_item) {
      $ticket_info = json_decode($order_item->ticket_info,true); 
      if ($order_item->ticket->connection_id == $connection->id) {
        if ($this->Tickets->consume($order_item->ticket->id,$order_item->ticket->voucher_type,$order_item->ticket->attraction->pricing_type,$order_item->ticket->stock,$order_item->qty,$order_item->date_of_visit,$order_item->time_of_visit) !== true) {
          return 'Ticket insuficient';
        }
        $api_sku_parts = explode('|',$order_item->ticket->api_sku);
        if ($api_sku_parts >= 2) {
          $this->rws_ticket_order_item_mapping[$api_sku_parts[0].'|'.$api_sku_parts[1]] = $order_item->id;
        } else {
          Log::write('error','Invalid RWS api sku '.$order_item->ticket->api_sku);
          return false;
        }
        
        $common_offers = $this->_buildCommonOffers($common_offers,$order_item,$order_item->qty,$order_item->ticket,$patron_info);
        // $common_offers[$order_item->offer_no] = $common_offer;
        if (!empty($ticket_info) && !empty($ticket_info['cost_price'])) {
          // $total = bcadd($total, bcmul($ticket_info['cost_price'],$order_item->qty));
          $total += floatval($ticket_info['cost_price']) * $order_item->qty;
        }
      } else {
        if ($order_item->ticket->type == 'complex') {
          $child_tickets = $this->Tickets->find()->matching('PackagesTickets',function ($q) use ($order_item,$connection) {
            return $q->where(['PackagesTickets.package_ticket_id'=>$order_item->ticket->id]);
          })
          ->contain(['Attractions'])
          ->where(['api_purchase'=>true,'connection_id'=>$connection->id])
          ->toArray();
          foreach ($child_tickets as $key => $child_ticket) {
            if ($this->Tickets->consume($child_ticket->id,$child_ticket->voucher_type,$child_ticket->attraction->pricing_type,$child_ticket->stock,$order_item->qty,$order_item->date_of_visit,$order_item->time_of_visit) !== true) {
              return 'Ticket insuficient';
            }
            $api_sku_parts = explode('|',$child_ticket->api_sku);
            if ($api_sku_parts >= 2) {
              $this->rws_ticket_order_item_mapping[$api_sku_parts[0].'|'.$api_sku_parts[1]] =$order_item->id ;
            } else {
              Log::write('error','Invalid RWS api sku '.$child_ticket->api_sku);
              return false;
            }
            $common_offers = $this->_buildCommonOffers($common_offers,$order_item,$order_item->qty,$child_ticket,$patron_info);
            if (!empty($ticket_info) && !empty($ticket_info['cost_price'])) {
              if (!empty($ticket_info[$child_ticket->sku_id]['cost_price'])) {
                $total += floatval($ticket_info[$child_ticket->sku_id]['cost_price']) * $order_item->qty;
              } else {
                $total += $child_ticket->cost_price * $order_item->qty;
              }
            }
          }
        }
      }
        
      
    }
    $data = [
      "commonOffers" => array_values($common_offers),
      "totalPrice" => $total,
      "PaymentMode" => "eWallet",
      "patronInfo" => $patron_info,
      "transactionNo" => $order->order_ref_id
    ];
    // debug($data);exit;
    $this->_logRequest($data,'create_booking');
    $data = json_encode($data,JSON_PRETTY_PRINT);
    $md5 = base64_encode(md5($data, true));
    $date = date('m/d/Y, h:i:s A');
    $url = '/b2b/transaction/v1/booking/create';
    $headers = $this->_buildHeaders('POST',$url,$date,$md5,$data);
    $response = $http->post($this->server_url.$url,$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
    $waiting_time = 0;
    if ($response->isOk()) {
      if (isset($response->getJson()['result'])) {
        $orderCreated = $response->getJson()['result'];
        if (!empty($orderCreated['bookingNo'])) {
          // if ($order->payment_method == 'credits') {
            // $status = $this->_getOrderStatus($orderCreated);
            // $while_ctr = 0;
            for ($try_ctr=0; $try_ctr < $TRY_TIMES; $try_ctr++) { 
              $status = $this->_getOrderStatus($orderCreated);
              Log::write('debug',$status);
              if ($status && strtolower($status['transStatus']) == 'creating') {
                sleep(2);
                $waiting_time += 2;
              } else {
                break;
              }
            }
            if ($status && strtolower($status['transStatus']) == 'waitingforpaying') {
              $payment = $this->_payOrder($order,$orderCreated);
              if ($payment) {
                /* we save the booking no to order_items first */
                if (!empty($orderCreated['bookingNo'])) {
                  foreach($order_items as $order_item) {
                    $_ticket_info = json_decode($order_item->ticket_info,true);
                    if (!empty($_ticket_info)) {
                      $_ticket_info['rws_booking_no'] = $orderCreated['bookingNo'];
                      $order_item->ticket_info = json_encode($_ticket_info);
                      $order_item->setDirty('ticket_info',true);
                    }
                  }
                  if (!$this->OrderItems->saveMany($order_items)) {
                    $this->error_code = 8011;
                    $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
                    $this->_logFailure(['reason'=>'Cannot save order booking no.','details'=>$order_items,'error_ref'=>$error_ref],$order);
                    return $error_ref; 
                  }
                }
                $TRY_TIMES = 10 + floor((20 - $waiting_time)/2);
                $waiting_time = 0;
                
                for ($try_ctr=0; $try_ctr < $TRY_TIMES; $try_ctr++) { 
                  $status = $this->_getOrderStatus($orderCreated);
                  if ($status && (in_array(strtolower($status['transStatus']), ['paying','paid','archiving']))) {
                    sleep(2);
                    $waiting_time += 2;
                  } else {
                    Log::write('debug',$status);
                    break;
                  }
                }
                $details = $this->_getOrderDetails($orderCreated);
                /* we check if not yet completed we try to wait 1s then try again */
                if (!empty($details) && !empty($details['bookingDetail']) && !in_array(strtolower($details['bookingDetail']['status']),['complete','confirmed'])) {
                  sleep(1);
                  $waiting_time += 1;
                  $details = $this->_getOrderDetails($orderCreated);
                }
                if (!empty($details) && !empty($details['bookingDetail']) && in_array(strtolower($details['bookingDetail']['status']),['complete','confirmed'])) {
                  return $this->_completeOrder($order,$order_items,$connection,$details,$this->rws_ticket_order_item_mapping);
                } else {
                  /* order not completed */
                  $this->error_code = 8004;
                  $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
                  $this->_logFailure(['reason'=>'Order paid but not completed','details'=>$details,'error_ref'=>$error_ref,'waiting_time'=>$waiting_time],$order);
                  $order->status = 'in_progress';
                  $this->Orders->save($order);
                  $order_to_complete = $this->OrdersToComplete->newEntity(['order_id'=>$order->id,'check_after'=>date('Y-m-d H:i:s',strtotime('+5 minutes'))]);
                  if (!$this->OrdersToComplete->save($order_to_complete)) {
                    Log::debug($order_to_complete->getErrors());
                  }
                  return $error_ref;
                }
              } else {
                /* payment not successful */
                $this->error_code = 8003;
                $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
                return $error_ref;
              }
            } else {
              $this->error_code = 8002;
              $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
              $this->_logFailure(['reason'=>'Not allowed to pay','details'=>$status,'error_ref'=>$error_ref,'waiting_time'=>$waiting_time],$order);
              return $error_ref;
            }
          // } else {
          //   // order not paid by credits
          // }
          // return true;
        }
        $this->error_code = 8001;
        $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
        $this->_logError($url,$data,$headers,$response,['reason'=>'Cannot create RWS order.','error_ref'=>$error_ref]);
        return $error_ref;
      } else {
        $this->error_code = 8001;
        $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
        $this->_logError($url,$data,$headers,$response,['reason'=>'Cannot create RWS order.','error_ref'=>$error_ref]);
        return $error_ref;
      }
    } else {
      $this->error_code = 8001;
      $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
      $this->_logError($url,$data,$headers,$response,['reason'=>'Cannot create RWS order.','error_ref'=>$error_ref]);
      return $error_ref;
    }
    return false;

  }

  private function _completeOrder($order,$order_items,$connection,$details,$rws_ticket_order_item_mapping) {
    $last_order_item = null;
    foreach ($details['bookingDetail']['offers'] as $key => $offer) {
      $filenames = [];
      $order_item_created = null;
      $first_visual_id = null;
      $first_eticket_data = null;
      $order_item = null;
      foreach ($offer['attraction'] as $key => $attraction) {
        $temp_api_sku = $attraction['offerNo'].'|'.$attraction['prodNo'];
        $order_item_id = !empty($rws_ticket_order_item_mapping[$temp_api_sku]) ?  $rws_ticket_order_item_mapping[$temp_api_sku] : null;
        if ($order_item_id) {
          foreach ($order_items as $key => $_loop_oi) {
            if ($_loop_oi->id == $order_item_id) {
              $order_item = $_loop_oi;
              $order_item_created = $order_item->created;
              $order_item->setDirty('vouchers',true);
              // $ticket_info = json_decode($order_item->ticket_info,true);
              // if (!empty($ticket_info)) {
              //   $ticket_info['rws_booking_no'] = $orderCreated['bookingNo'];
              //   $order_item->ticket_info = json_encode($ticket_info);
              //   $order_item->setDirty('ticket_info',true);
              // }
              break;
            }
          }
        }
        // foreach ($order_items as $key => $_loop_oi) {
        //   if ($_loop_oi['id'] == $attraction['comment']) {
        //     $order_item = $_loop_oi;
        //     $last_order_item = $order_item;
        //     $order_item_created = $order_item->created;
        //     break;
        //   }
        // }
        // if (empty($order_item)) {
        //   $order_item = $last_order_item;
        // }
        
        $eticket_details = $this->_getEticketsData($details,$attraction);
        if ($eticket_details === null) {
          Log::write('debug',$eticket_details);
          return false;
        } else {
          // Log::write('debug',$eticket_details);
          // debug($eticket_details);
          if (!empty($eticket_details['is_package'])) {
            // $order_item->api_vouchers[] = $eticket_details['details'][0]['visualID'];
            foreach ($eticket_details['details'] as $key => $eticket_detail) {
              // $order_item->api_vouchers[] = $eticket_detail['visualID'];
              $filenames[] = $this->_createPdf($order,$order_item->ticket,$order_item,$eticket_detail,false);
            }
            $first_visual_id = $eticket_details['details'][0]['visualID'];
            $first_eticket_data = $eticket_details['details'][0];
            if (strtolower($offer['attractionGroupType']) != 'fixed') {
              /* for packages upload to s3 already then clear empty filenames so it will not be reuploaded */
              $s3_full_path = $this->_mergeAndUploadToS3($filenames,$first_visual_id,$order_item_created);
              $order_item->vouchers[] = $this->_createApiVoucher($order,$order_item,$s3_full_path,$first_visual_id,$eticket_details['details'][0],$connection->id);
              $order_item->setDirty('vouchers',true);
              $filenames = [];
            }
            
          } else {
            if (empty($first_visual_id)) {
              $first_visual_id = $eticket_details['visualID'];
              $first_eticket_data = $eticket_details;
            }
            
            if (strtolower($offer['attractionGroupType']) == 'fixed') {// if (count($offer['attraction']) > 1) {
              // check if attraction['admission'] in ['Retail Vouchers','Meal Vouchers'] just create a api voucher then clear filenames
              $filenames[] = $this->_createPdf($order,$order_item->ticket,$order_item,$eticket_details,false);
            } else {
              $this->_createPdf($order,$order_item->ticket,$order_item,$eticket_details);
              $filename = 'vouchers/'.$order_item->created->format('Y/md').'/'.$eticket_details['visualID'].'.pdf';
              // debug($eticket_details['visualID']);
              $order_item->vouchers[] = $this->_createApiVoucher($order,$order_item,$filename,$eticket_details['visualID'],$eticket_details,$connection->id);
              $order_item->setDirty('vouchers',true);
              $filenames = [];
            }
          }

        }
        
      }
      if (count($filenames) > 1) {
        // debug($filenames);
        $s3_full_path = $this->_mergeAndUploadToS3($filenames,$first_visual_id,$order_item_created);
        $order_item->vouchers[] = $this->_createApiVoucher($order,$order_item,$s3_full_path,$first_visual_id,$first_eticket_data,$connection->id);
      }
      $order_item->setDirty('vouchers',true);
    }
    
    return $order_items;
  }

  private function _createApiVoucher($order,$order_item,$s3_full_path,$first_visual_id,$eticket_detail,$connection_id){
    $voucher = $this->Vouchers->newEntity([
      'voucher_type'=>'api',
      'order_item_id'=>$order_item->id,
      'created'=>date('Y-m-d H:i:s')
    ]);
    $api_pdf = $this->ApiPdfs->newEntity([
      'pdf_filename'=>$first_visual_id.'.pdf',
      'code'=>$first_visual_id,
      'filename'=>$s3_full_path,
      'validity_start_date'=>$eticket_detail['validDayFrom'],
      'validity_end_date'=>$eticket_detail['validDayTo'],
      'status'=>'purchased',
      'sold'=> true
    ]);
    
    
    $api_sku = $eticket_detail['offerNo'].'|'.$eticket_detail['prodNo'];
    $ticket = $this->Tickets->find()->where(['id'=>$order_item->ticket_id])->first();
    $ticket_sku_id = '';
    if (!empty($ticket->api_sku) && $ticket->api_sku == $api_sku ) {
      $ticket_sku_id = $ticket->sku_id;
    } else {
      $ticket = $this->Tickets->find()->where(['id'=>$order_item->ticket_id])->contain(['ChildTickets'=>function($q) use($api_sku) {
          return $q->where(['api_sku like'=>$api_sku.'%']);
      }])->first();
      if ($ticket && $ticket->child_tickets) {
        $ticket_sku_id = $ticket->child_tickets[0]->sku_id;
      }
    }
    $api_pdf->purchased_ticket_sku_id = $ticket_sku_id;
    $api_pdf->ticket_sku_id = $ticket_sku_id;
    if ($this->ApiPdfs->save($api_pdf)) {
      $voucher->code_id = $api_pdf->code;
    } else {
      $this->_logFailure(['reason'=>'cannot create ApiPdf','eticket_detail'=>$eticket_detail,'api_pdf'=>$api_pdf],$order);
      return false;
    }
    $voucher->connection_id = $connection_id;
    $voucher->ticket_name = !empty($ticket) ? $ticket->name : $eticket_detail['ticketDescription'];
    return $voucher;
  }

  private function _mergeAndUploadToS3($filenames,$order_item_id,$created) {
    $merged_filename = $order_item_id;
    $merge_output_file = $this->_pdfMerge($filenames,$merged_filename);
    $full_output_filename = WWW_ROOT. 'files'.DS.'pdf'.DS.$merge_output_file;
    $s3_filename = $order_item_id;
    $s3_full_path = 'vouchers/'.$created->format('Y/md').'/'.$s3_filename.'.pdf';
    // debug($full_output_filename);
    // debug('vouchers/'.$created->format('Y/md').'/'.$s3_filename.'.pdf');exit;
    $this->_putObject($s3_full_path,$full_output_filename,'application/pdf');
    return $s3_full_path;
  }

  private function _pdfMerge($files, $ref_id) {
    /*ghostsript*/
    $folder = new \Cake\Filesystem\Folder(WWW_ROOT. 'files/pdf',true,0775);
    $output_file = $ref_id.'.pdf';
    
    if (Configure::read('ENVI') == 'DEV') {
        #$cmd = "/usr/local/bin/gs -q -dNOPAUSE -dBATCH -dAutoRotatePages=1 -sPAPERSIZE=legal -sDEVICE=pdfwrite -sOutputFile=$output_file ";
        // $cmd = "/usr/local/bin/gs";
        // $cmd = "gs";
        $cmd = '\"program files"\gs\gs9.52\bin\gswin64';
    } else {
        #$cmd = "gs -dNOPAUSE -dBATCH -dAutoRotatePages=1 -sPAPERSIZE=legal -sDEVICE=pdfwrite -sOutputFile=$output_file ";
        $cmd = "gs";
        // $cmd = "/program files/gs/gs9.52/bin/gswin64";
    }
    $full_output_file = WWW_ROOT. 'files/pdf/'.$ref_id.'.pdf';
    $cmd .= " -sDEVICE=pdfwrite -dNOPAUSE -dQUIET -dBATCH -sOutputFile=$full_output_file ";
    // debug($files);
    foreach($files as $file) {
        $cmd .= $file." ";
    }
    try {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $p_string = "start {$cmd}";
            pclose(popen($p_string, "r"));
            sleep(2);
        } else {
            exec($cmd);
        }
    } catch (Exception $exc) {
        echo 'Error: '.$exc;
    }
    return $output_file;
  }

  private function _getEticketsData($details,$attraction) {

    if ($attraction['packageSpec'] == null) {
      $eticket_details = [
        'bookingNo'=>$details['bookingDetail']['bookingNo'],
        'offerNo'=> $attraction['offerNo'],
        'visualID'=>$attraction['visualID'],
        'themePark'=>$attraction['themePark'],
        'ticketDescription'=>$attraction['ticketDescription'],
        'prodNo'=>$attraction['prodNo'],
        'pluCode'=>$attraction['pluCode'],
        'validDayFrom'=>$attraction['validDayFrom'],
        'validDayTo'=>$attraction['validDayTo'],
        'mainTaName'=>!empty($details['bookingDetail']['patronInfo']) ? $details['bookingDetail']['patronInfo']['mainTaName'] : '',
        'validTimeFrom'=> !empty($attraction['validTimes']) ? $attraction['validTimes'][0]['validTimeFrom'] : '',
        'language' => $attraction['language']
      ];
      return $eticket_details;
    } else {
      if ($attraction['ticketType'] == 'MPP') {
        $visual_ids = [];
        $visual_ids[] = $attraction['visualID'];
        $eticket_details = ['is_package'=>true];
        $eticket_details['details'] = [[
          'bookingNo'=>$details['bookingDetail']['bookingNo'],
          'offerNo'=> $attraction['offerNo'],
          'visualID'=>$attraction['visualID'],
          'themePark'=>$attraction['themePark'],
          'prodNo'=>$attraction['prodNo'],
          'pluCode'=>$attraction['pluCode'],
          'ticketDescription'=>$attraction['ticketDescription'],
          'validDayFrom'=>$attraction['validDayFrom'],
          'validDayTo'=>$attraction['validDayTo'],
          'mainTaName'=>!empty($details['bookingDetail']['patronInfo']) ? $details['bookingDetail']['patronInfo']['mainTaName'] : '',
          'validTimeFrom'=> !empty($attraction['validTimes']) ? $attraction['validTimes'][0]['validTimeFrom'] : '',
          'language' => $attraction['language']
        ]];
        $packageSpec = json_decode($attraction['packageSpec'],true);
        if ($packageSpec && $packageSpec['packageSpecAttributes']) {
          foreach ($packageSpec['packageSpecAttributes'] as $key => $package_ticket) {
            if (strtolower($package_ticket['ticketType']) == 'voucher') {
              $visual_ids[] = $package_ticket['visualId'];
              $eticket_details['details'][] = [
                'bookingNo'=>$details['bookingDetail']['bookingNo'],
                'offerNo'=> $attraction['offerNo'],
                'visualID'=>$package_ticket['visualId'],
                'themePark'=>$package_ticket['themeParks'],
                'ticketDescription'=>$package_ticket['pluName'],
                'validDayFrom'=>$attraction['validDayFrom'],
                'validDayTo'=>$attraction['validDayTo'],
                'pluCode'=>$package_ticket['pluCode'],
                'mainTaName'=>!empty($details['bookingDetail']['patronInfo']) ? $details['bookingDetail']['patronInfo']['mainTaName'] : '',
                'validTimeFrom'=> !empty($attraction['validTimes']) ? $attraction['validTimes'][0]['validTimeFrom'] : '',
                'language' => $attraction['language']
              ];
            }
          }
        }
        return $eticket_details;
      } else {
        $packageSpec = json_decode($attraction['packageSpec'],true);
        $eticket_details = ['is_package'=>true,'details'=>[]];
        if ($packageSpec && $packageSpec['packageSpecAttributes']) {
          foreach ($packageSpec['packageSpecAttributes'] as $key => $package_ticket) {
            // if (strtolower($package_ticket['ticketType']) == 'voucher') {
              $visual_ids[] = $package_ticket['visualId'];
              $eticket_details['details'][] = [
                'bookingNo'=>$details['bookingDetail']['bookingNo'],
                'offerNo'=> $attraction['offerNo'],
                'visualID'=>$package_ticket['visualId'],
                'themePark'=>$package_ticket['themeParks'],
                'ticketDescription'=>$package_ticket['pluName'],
                'validDayFrom'=>$attraction['validDayFrom'],
                'prodNo'=>$attraction['prodNo'],
                'validDayTo'=>$attraction['validDayTo'],
                'pluCode'=>$package_ticket['pluCode'],
                'mainTaName'=>!empty($details['bookingDetail']['patronInfo']) ? $details['bookingDetail']['patronInfo']['mainTaName'] : '',
                'validTimeFrom'=> !empty($attraction['validTimes']) ? $attraction['validTimes'][0]['validTimeFrom'] : ''
              ];
            // }
          }
        }
        return $eticket_details;
      }
    }
    return null;
  }

  private function _getOrderStatus($orderCreated) {
    $http = new Client();
    $data = [
      "bookingNo" => $orderCreated['bookingNo']
    ];
    $md5 = '';
    $date = date('m/d/Y, h:i:s A');
    $url = '/b2b/transaction/v1/booking/status';
    $headers = $this->_buildHeaders('GET',$url.'?'. http_build_query($data),$date,$md5,$data);
    $this->_logRequest($data,'booking_status');
    $response = $http->get($this->server_url.$url,$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
    if ($response->isOk()) {
      $orderStatus = $response->getJson()['result'];
      return $orderStatus;
    }  else {
      $this->_logError($url,$data,$headers,$response);
    }
    return false;
  }

  private function _payOrder($order,$orderStatus) {
    $http = new Client();
    $data = '{"orderNo":"'.$orderStatus['bookingNo'].'","paymentMode":"eWallet"}';
    $md5 = base64_encode(md5($data, true));
    $url = '/b2b/payment/v1/payment/paymentOrder';
    $date = date('m/d/Y, h:i:s A');
    $headers = $this->_buildHeaders('POST',$url,$date,$md5,$data);
    $this->_logRequest($data,'pay_order');
    $response = $http->post($this->server_url.$url,$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
    if ($response->isOk()) {
      $payOrder = $response->getJson();
      if ($payOrder['resultCode'] == 0) {
        return true;
      }
      $this->error_code = 8003;
      $error_ref = $this->error_code.'-'.md5($order->order_ref_id).date('mdHis');
      $this->_logFailure(['reason'=>'Payment failed','details'=>$payOrder,'error_ref'=>$error_ref],$order);
      return false;
    } else {
      $this->_logError($url,$data,$headers,$response);
    }
    return false;
  }

  private function _getOrderDetails($orderCreated) {
      $http = new Client();
      $data = [
        "bookingNo" => $orderCreated['bookingNo']
      ];
      $md5 = '';
      $date = date('m/d/Y, h:i:s A');
      $url = '/b2b/transaction/v1/booking/query';
      $headers = $this->_buildHeaders('GET',$url.'?'. http_build_query($data),$date,$md5,$data);
      $this->_logRequest($data,'query_booking');
      $response = $http->get($this->server_url.$url,$data,['headers' => $headers, 'ssl_verify_peer' => false, 'ssl_verify_peer_name' => false]);
      if ($response->isOk()) {
        $orderStatus = $response->getJson()['result'];
        return $orderStatus;
      }  else {
        $this->_logError($url,$data,$headers,$response);
      }
      return null;
    }
   
  private function _createPdf($order,$ticket,$order_item,$details,$upload = true) {
    $CakePdf = new \CakePdf\Pdf\CakePdf();
    $offer_no_plu_code = $details['offerNo'].'_'.$details['pluCode'];
    // Log::write('debug',['offer_no_plu_code'=>$offer_no_plu_code,'template'=>!empty($this->templates[$offer_no_plu_code]) ? $this->templates[$offer_no_plu_code] :'']);
    $CakePdf->margin(0, 0, 0, 0);
    if (!empty($details['pluCode']) && !empty($this->templates[$offer_no_plu_code])) {
      $CakePdf->template('api/rws/'.$this->templates[$offer_no_plu_code], 'pdf');
    } else {
      if (!empty($ticket->voucher_template) && in_array($ticket->voucher_template, Configure::read('Default.voucher_templates')) ) {
        $CakePdf->template('api/rws/'.$ticket->voucher_template, 'pdf');
      }
      else {
        $CakePdf->template('api/'.'default_voucher', 'pdf');
        $CakePdf->margin(15, 20, 25, 20);
      }
    }
    
    

    $barcode = '';
    $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
    $barcode_big = '<img style="width:3.5cm;height:1cm" src="data:image/png;base64,' . base64_encode($generator->getBarcode($details['visualID'], $generator::TYPE_CODE_128,2,75)) . '">';
    $barcode_small = '<img style="width:3.5cm;height:1cm" src="data:image/png;base64,' . base64_encode($generator->getBarcode($details['visualID'], $generator::TYPE_CODE_128,1,40)) . '">';
    
    
    $CakePdf->viewVars([
      'code'=>$details['visualID'],
      'booking_no'=>$details['bookingNo'],
      'order'=>$order,
      'barcode_big'=>$barcode_big,
      'barcode_small'=>$barcode_small,
      'order_item'=>$order_item,
      'visualID'=>$details['visualID'],
      'themePark'=>$details['themePark'],
      'ticketDescription'=>$details['ticketDescription'],
      'validDayFrom'=>$details['validDayFrom'],
      'validDayTo'=>$details['validDayTo'],
      'mainTaName'=>$details['mainTaName'],
      'validTimeFrom'=> $details['validTimeFrom']
    ]);
    
    if ($upload) {
      $pdf_filename = 'vouchers/'.$order_item->created->format('Y/md').'/'.$details['visualID'].'.pdf';
      $this->_putObjectAsStream($pdf_filename,$CakePdf->output(),'application/pdf');
      return null;
    } else {
      // $pdf = $CakePdf->output();
      $dir = new Folder( TMP.'merged_pdf', true, 0755);
      $tmp_filename = TMP.'merged_pdf'.DS. $details['visualID'].'.pdf';
      $pdf = $CakePdf->write($tmp_filename);
      return $tmp_filename;
    }
  }

  private function _buildHeaders($method,$url,$date,$md5,$data) {
    $nonce = Text::uuid();
    $textToSign = $method."\n*/*\n".$md5.
      "\napplication/json\n".
      $date .
      "\nx-ca-key:".$this->api_key.
      "\nx-ca-nonce:".$nonce.
      "\nx-ca-signaturemethod:".$this->signature_method.
      "\n".$url;
    $signature = base64_encode(hash_hmac('SHA256', $textToSign, $this->secret_key, true));
    $headers = [
      'Date' => $date,
      'Content-MD5' => $md5,
      'X-Ca-Nonce' => $nonce,
      'X-Ca-Key' => $this->api_key,
      'X-Ca-Signature' => $signature,
      'X-Ca-SignatureMethod' => $this->signature_method,
      'X-Ca-Signature-Headers' => $this->signature_headers,
      'Accept' => '*/*',
      'Content-Type' => 'application/json',
      'Accept-Encoding' => "gzip, deflate, br",
      'Connection' => "keep-alive"
    ];

    return $headers;
  }

  private function _putObjectAsStream($key, $string, $contentType=null){
    $s3 = S3Client::factory(Configure::read('AWS'));
    $_main_folder = Configure::read('Default.aws_prefix');
    if ($contentType==null) {
      $contentType = $this->getMimeType($_main_folder.$key);
    }
    try {
      $result = $s3->putObject(array(
        'Bucket'        => Configure::read('AWS.Bucket'),
        'Key'          => $_main_folder.$key,
        'Body'   => $string,
        'ContentType'  => $contentType,
        'StorageClass' => 'REDUCED_REDUNDANCY',
      ));
      if($result){   
        return $_main_folder.$key;
      }
    } 
    catch (S3Exception $e) {
      \Cake\Log\Log::debug($e);
      return false;
    }
    catch (\Exception $e) {
      \Cake\Log\Log::debug($e);
      return false;
    }
  }

  private function _putObject($key,$file, $contentType = null){
    $s3 = S3Client::factory(Configure::read('AWS'));
    $_main_folder = Configure::read('Default.aws_prefix');
    if ($contentType==null) {
      $contentType = $this->getMimeType($_main_folder.$key);
    }
    try {
      $result = $s3->putObject(array(
        'Bucket'        => Configure::read('AWS.Bucket'),
        'Key'          => $_main_folder.$key,
        'SourceFile'   => $file,
        'ContentType'  => $contentType,
        'StorageClass' => 'REDUCED_REDUNDANCY',            
      ));
      if($result){   
        return $_main_folder.$key;
      }
    } 
    catch (S3Exception $e) {
      \Cake\Log\Log::debug($e);
      return false;
    }
    catch (\Exception $e) {
      \Cake\Log\Log::debug($e);
      return false;
    }
  }

  private function _logRequest($data,$action) {
    $error = [
      'action'=>$action,
      'connection'=>'RWS',
      'data'=>$data
    ];
    $api_log = new File(LOGS.'/rws-requests'.date('Ymd').'.log', true, 0644);    
    $api_log->open('a');
    $api_log->append(PHP_EOL.date('Y-m-d H:i:s').': '.json_encode($error,JSON_PRETTY_PRINT));
    $api_log->close();
    // $this->_mail('RWS Exception','connection_exception','RWS Connection Exception Occured',Configure::read('Default.admin_email'),$error,null,Configure::read('Default.exception_emails'));
  }

  private function _logError($url,$data,$headers,$response,$error_ref = null) {
    $error = ['url'=>$this->server_url.$url,
      'connection'=>'RWS',
      'data'=>$data,
      'headers'=>$headers,
      'response'=>[
        'statusCode'=>$response->getStatusCode(),
        'reasonPhrase'=>$response->getReasonPhrase(),
        'errorMessage'=>!empty($response->getHeaders()['X-Ca-Error-Message']) ? $response->getHeaders()['X-Ca-Error-Message'] : '',
      ],
      'error_reference'=>$error_ref
    ];
    $api_log = new File(LOGS.'/api-failures'.date('Ymd').'.log', true, 0644);    
    $api_log->open('a');
    $api_log->append(PHP_EOL.date('Y-m-d H:i:s').': '.json_encode($error,JSON_PRETTY_PRINT));
    $api_log->close();
    Log::write('error',$response);
    $this->_mail('RWS Exception','connection_exception','RWS Connection Exception Occured',Configure::read('Default.admin_email'),$error,null,Configure::read('Default.exception_emails'));
  }

  private function _logFailure($data,$order) {
    $error = [
      'connection'=>'RWS',
      'order_id'=>$order->id,
      'order_ref_id'=>$order->order_ref_id,
      'data'=> $data
    ];
    $api_log = new File(LOGS.'/api-failures'.date('Ymd').'.log', true, 0644);    
    $api_log->open('a');
    $api_log->append(PHP_EOL.date('Y-m-d H:i:s').': '.json_encode($error,JSON_PRETTY_PRINT));
    $api_log->close();
    try {
      $this->_mail('RWS Exception','connection_exception','Failed to Complete RWS Transaction',Configure::read('Default.admin_email'),$error,null,Configure::read('Default.exception_emails'));
    } catch (Exception $e) {
      /* error sending mail */
    }
    
  }

  private function _mail($name,$template, $subject, $to, $data, $cc = null,$bcc = null, $attachments = null) {
    $Email = new Email();
    $Email->addHeaders(['Content-Type: text/html; charset=UTF-8']);
    $Email->viewBuilder()->setTemplate($template);
    $Email->setViewVars(array('data' => $data));
    $Email->setEmailFormat('html');
    $Email->setTo($to);
    if (!empty($cc)) {
        $Email->setCc($cc);
    }
    if (!empty($bcc)) {
        $Email->addBcc($bcc);
    }
    if (Configure::read('Default.admin_email')) {
        $Email->addBcc(Configure::read('Default.admin_email'));
    }
    $Email->setSubject($subject);


    /* set attachments if available */
    if (!empty($attachments)) {
      $Email->setAttachments($attachments);
    }
    /* end set attachments */

    try {
      $Email->send();
    } catch (\Cake\Network\Exception\SocketException $e) {
      $Email->setTransport('default');
      $Email->setSubject('[TDP Email Error] '.$Email->getSubject());
      if (Configure::read('Default.admin_email')) {
        $Email->setTo(Configure::read('Default.admin_email'));
      }
      $Email->send();
    } catch (Exception $e) {
      $Email->setTransport('default');
      $Email->setSubject('[TDP Email Error] '.$Email->getSubject());
      if (Configure::read('Default.admin_email')) {
        $Email->setTo(Configure::read('Default.admin_email'));
      }
      $Email->send();
    }
  }

  /**
   * When order is paid to rws but error occured we will let the transaction complete and check with what is the cause of the error so we can manually set the vouchers 
   * When order is not yet paid to rws we will revert the transaction and show an error
   * 
   * */


}
