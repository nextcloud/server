/*
 * This code exists for the sole purpose to serve as another implementation
 * of the "private" password hashing method implemened in PasswordHash.php
 * and thus to confirm that these password hashes are indeed calculated as
 * intended.
 *
 * Other uses of this code are discouraged.  There are much better password
 * hashing algorithms available to C programmers; one of those is bcrypt:
 *
 *	http://www.openwall.com/crypt/
 *
 * Written by Solar Designer <solar at openwall.com> in 2005 and placed in
 * the public domain.
 *
 * There's absolutely no warranty.
 */

#include <string.h>
#include <openssl/md5.h>

#ifdef TEST
#include <stdio.h>
#endif

static char *itoa64 =
	"./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

static void encode64(char *dst, char *src, int count)
{
	int i, value;

	i = 0;
	do {
		value = (unsigned char)src[i++];
		*dst++ = itoa64[value & 0x3f];
		if (i < count)
			value |= (unsigned char)src[i] << 8;
		*dst++ = itoa64[(value >> 6) & 0x3f];
		if (i++ >= count)
			break;
		if (i < count)
			value |= (unsigned char)src[i] << 16;
		*dst++ = itoa64[(value >> 12) & 0x3f];
		if (i++ >= count)
			break;
		*dst++ = itoa64[(value >> 18) & 0x3f];
	} while (i < count);
}

char *crypt_private(char *password, char *setting)
{
	static char output[35];
	MD5_CTX ctx;
	char hash[MD5_DIGEST_LENGTH];
	char *p, *salt;
	int count_log2, length, count;

	strcpy(output, "*0");
	if (!strncmp(setting, output, 2))
		output[1] = '1';

	if (strncmp(setting, "$P$", 3))
		return output;

	p = strchr(itoa64, setting[3]);
	if (!p)
		return output;
	count_log2 = p - itoa64;
	if (count_log2 < 7 || count_log2 > 30)
		return output;

	salt = setting + 4;
	if (strlen(salt) < 8)
		return output;

	length = strlen(password);

	MD5_Init(&ctx);
	MD5_Update(&ctx, salt, 8);
	MD5_Update(&ctx, password, length);
	MD5_Final(hash, &ctx);

	count = 1 << count_log2;
	do {
		MD5_Init(&ctx);
		MD5_Update(&ctx, hash, MD5_DIGEST_LENGTH);
		MD5_Update(&ctx, password, length);
		MD5_Final(hash, &ctx);
	} while (--count);

	memcpy(output, setting, 12);
	encode64(&output[12], hash, MD5_DIGEST_LENGTH);

	return output;
}

#ifdef TEST
int main(int argc, char **argv)
{
	if (argc != 3) return 1;

	puts(crypt_private(argv[1], argv[2]));

	return 0;
}
#endif
